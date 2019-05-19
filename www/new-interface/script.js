// Code for comparison of arrays, source https://stackoverflow.com/questions/7837456/how-to-compare-arrays-in-javascript
// Warn if overriding existing method
if(Array.prototype.equals)
    console.warn("Overriding existing Array.prototype.equals. Possible causes: New API defines the method, there's a framework conflict or you've got double inclusions in your code.");
// attach the .equals method to Array's prototype to call it on any array
Array.prototype.equals = function (array) {
    // if the other array is a falsy value, return
    if (!array)
        return false;

    // compare lengths - can save a lot of time
    if (this.length !== array.length)
        return false;

    for (var i = 0, l=this.length; i < l; i++) {
        // Check if we have nested arrays
        if (this[i] instanceof Array && array[i] instanceof Array) {
            // recurse into the nested arrays
            if (!this[i].equals(array[i]))
                return false;
        }
        else if (this[i] !== array[i]) {
            // Warning - two different object instances will never be equal: {x:20} != {x:20}
            return false;
        }
    }
    return true;
};
// Hide method from for-in loops
Object.defineProperty(Array.prototype, "equals", {enumerable: false});


//Code for computation difference between dates, source https://stackoverflow.com/questions/542938/how-do-i-get-the-number-of-days-between-two-dates-in-javascript
function parseDate(str) {
    var mdy = str.split('-');
    return new Date(mdy[0], mdy[1]-1, mdy[2]);
}

function datediff(first, second) {
    return Math.round((parseDate(second)-parseDate(first))/(1000*60*60*24));
}


var charts_options = {
    isStacked: 'relative',
    //height: 300,
    legend: {position: 'top', maxLines: 3},
    title: 'Groups',
    explorer: {
        actions: ['dragToZoom', 'rightClickToReset'],
        axis: 'vertical',
        keepInBounds: true,
        maxZoomIn: 12.0
    },
    hAxis: {title: 'Estimated number of keys added per day (in average)',  titleTextStyle: {color: '#333'}, slantedText:true, slantedTextAngle:50},
    vAxis: {minValue: 0, logScale: false}
};

var API_PREXI = "/api/cmocl";
function cmocl_get_dates(source, period, callback) {
    $.get(API_PREXI+"/"+source+"/"+period, callback);
}

function cmocl_get_results(source, period, from, to, callback) {
    $.get(API_PREXI+"/"+source+"/"+period+"/"+from+"/"+to, callback);
}

google.charts.load('current', {'packages':['corechart']});
google.charts.setOnLoadCallback(initCharts);

var rapid7_results = null;
var ct_results = null;

function initCharts() {
    var rapid7_chart = new google.visualization.LineChart(document.getElementById('rapid7_chart_div'));
    cmocl_get_dates("rapid7", "occasional", function(dates) {
        dates.sort();

        var r7from = $("#r7-from");
        r7from.empty();
        var r7to = $("#r7-to");
        r7to.empty();
        $.each(dates, function(key, date) {
            r7from.append($("<option></option>").attr("value", date).text(date));
            r7to.append($("<option></option>").attr("value", date).text(date));
        });

        dates.reverse();
        var lastIndex = Math.min(dates.length, 31 || dates.length);
        r7from.val(dates[lastIndex-1]);
        r7to.val(dates[0]);
        cmocl_get_results("rapid7", "occasional", dates[lastIndex-1], dates[0], function(results) {
            rapid7_results = results;
            drawEstimationResults(rapid7_chart, results);
        });

        r7from.on('change', function() {
            cmocl_get_results("rapid7", "occasional", r7from.val(), r7to.val(), function(results) {
                rapid7_results = results;
                drawEstimationResults(rapid7_chart, results);
            });
        });
        r7to.on('change', function() {
            cmocl_get_results("rapid7", "occasional", r7from.val(), r7to.val(), function(results) {
                rapid7_results = results;
                drawEstimationResults(rapid7_chart, results);
            });
        });
    });

    var ct_chart = new google.visualization.LineChart(document.getElementById('ct_chart_div'));
    cmocl_get_dates("ct", "day", function(dates) {
        dates.sort();

        var ctfrom = $("#ct-from");
        ctfrom.empty();
        var ctto = $("#ct-to");
        ctto.empty();
        $.each(dates, function(key, date) {
            ctfrom.append($("<option></option>").attr("value", date).text(date));
            ctto.append($("<option></option>").attr("value", date).text(date));
        });

        dates.reverse();
        var lastIndex = Math.min(dates.length, 31 || dates.length);
        ctfrom.val(dates[lastIndex-1]);
        ctto.val(dates[0]);
        cmocl_get_results("ct", "day", dates[lastIndex-1], dates[0], function(results) {
            ct_results = results;
            drawEstimationResults(ct_chart, results);
        });

        ctfrom.on('change', function() {
            cmocl_get_results("ct", "day", ctfrom.val(), ctto.val(), function(results) {
                ct_results = results;
                drawEstimationResults(ct_chart, results);
            });
        });
        ctto.on('change', function() {
            cmocl_get_results("ct", "day", ctfrom.val(), ctto.val(), function(results) {
                ct_results = results;
                drawEstimationResults(ct_chart, results);
            });
        });
    });

    $("a[href='#r7']").on('shown.bs.tab', function (e) {
        drawEstimationResults(rapid7_chart, rapid7_results);
    });

    $("a[href='#ct']").on('shown.bs.tab', function (e) {
        drawEstimationResults(ct_chart, ct_results);
    });
}

function drawEstimationResults(chart, results) {
    var groups = [];
    var days = [];
    var i, res, probabilities;
    for (i=1; i<results.length;i++) {
        days.push(datediff(results[i-1]["date"], results[i]["date"]))
    }

    // Get groups from all estimation with more than 1%
    for (i=1; i<results.length;i++) {
        res = results[i];
        probabilities = res["estimation"]["probability"];
        for (var group in probabilities){
            if (probabilities.hasOwnProperty(group)) {
                var val = parseFloat(probabilities[group]);
                if (val >= 0.0001) {
                    var sources = res["estimation"]["groups"][group];
                    var alreadyIn = false;
                    for (var o=0; o<groups.length; o++) {
                        if (groups[o].equals(sources)){
                            alreadyIn = true;
                            break;
                        }
                    }
                    if (!alreadyIn) {
                        groups.push(sources);
                    }
                }
            }
        }
    }

    // Constructing data for chart
    var header = ["View"];
    groups.forEach(function (group) {
        header.push(group.join(", "));
    });
    var data = [header];
    for (i=1; i<results.length;i++) {
        res = results[i];
        var row = [res["date"]];
        probabilities = res["estimation"]["probability"];
        var frequencies = res["estimation"]["frequencies"];
        var keys = 0;
        for (var f in frequencies) {
            if (!frequencies.hasOwnProperty(f)) {
                continue;
            }
            keys += frequencies[f];
        }
        var keysPerDay = keys/days[i-1];
        var gr = res["estimation"]["groups"];
        groups.forEach(function (group) {
            var value = 0;
            for (var g in gr) {
                if (!gr.hasOwnProperty(g)) {
                    continue;
                }
                if (gr[g].equals(group)) {
                    value = Math.round(parseFloat(probabilities[g])*keysPerDay);
                }
            }
            row.push(value);
        });
        data.push(row);
    }

    chart.draw(google.visualization.arrayToDataTable(data), charts_options);
}















var droppedFiles = false;
var timer;
var firstResults = true;
var groups = {
    "sw": null,
    "hw": null,
    "both": null
};

$(document).ready(function () {

    // $('body').scrollspy({ target: '#navbarCollapse' });

    $('body').scrollspy({
        offset: 60,
        target: '#navbarCollapse'
    });

    var form = document.querySelector('#keysForm');
    var textarea = form.querySelector('textarea');
    var progressBar = document.querySelector('.nav-progress-item');

    function showResults(data, sameSourceMultiKeysResults, typeFlag) {
        if (data.correctKeys === 0) {
            $("#foundRSAKeys").html("We have not found any RSA public key.");
            $('#details-tab').addClass('disabled');
            $('.onlyForSomeKeys div.card').hide();
        }
        else {
            if (data.correctKeys === 1) {
                $("#foundRSAKeys").html("We have found <b>one</b> RSA public key");
            }
            else {
                $("#foundRSAKeys").html("We have found <b>"+data.correctKeys+"</b> RSA public keys." + (data.correctKeys !== data.uniqueKeys ? '<br>'+data.uniqueKeys+' of them are unique.' : ''));
            }
            if (data.containerResults[0]['value'] != null) {
                var mostPropGrop = data.containerResults[0]['group'];
                if (groups[typeFlag][mostPropGrop].length > 1) {
                    $("#mostProbableSource").html("The most probable sources are <b>" + groups[typeFlag][mostPropGrop].join(", ") + "</b>.");
                    $("#resultsAccuracy").html("We are <b>" + (data.containerResults[0]['value']*100).toFixed(0) + " %</b> sure, that your keys were generated by these sources.");
                }
                else {
                    $("#mostProbableSource").html("The most probable source is <b>" + groups[typeFlag][mostPropGrop][0] + "</b>.");
                    $("#resultsAccuracy").html("We are <b>" + (data.containerResults[0]['value']*100).toFixed(0) + " %</b> sure, that your keys were generated by this source.");
                }

                var canBeFromOpenssl = true;
                var canBeFromOpensslFIPS = true;
                var canBeFromMicrosoft = true;
                var canBeFromGroup12 = true;
                data.containerResults.forEach(function (obj) {
                    if (obj['value'] === null) {
                        if (obj['group'] === 'VII') canBeFromOpenssl = false;
                        if (obj['group'] === 'XV') canBeFromMicrosoft = false;
                        if (obj['group'] === 'XVI') canBeFromGroup12 = false;
                        if (obj['group'] === 'XVII') canBeFromOpensslFIPS = false;
                    }
                });

                if (!canBeFromOpenssl || !canBeFromMicrosoft || !canBeFromGroup12 || !canBeFromOpensslFIPS) {
                    var arr = [];
                    if (!canBeFromOpenssl) arr.push("OpenSSL");
                    if (!canBeFromMicrosoft) arr.push("Microsoft implementations, Bouncy Castle 1.54, mbedTLS >= 2.9.0");
                    if (!canBeFromGroup12) arr.push("mbedTLS <=2.8.0, OpenJDK, FlexiProvider, Bouncy Castle 1.53");
                    if (!canBeFromOpensslFIPS) arr.push("OpenSSL FIPS, PGPSDK 4, Nettle >=3.2");
                    $("#negativeResults").html("We are sure that your key"+(groups[typeFlag][mostPropGrop].length > 1 ? "s" : "")+" could not be generated by these software libraries: "+arr.join(", ")+".");
                }
                else {
                    $("#negativeResults").html("Your key"+(groups[typeFlag][mostPropGrop].length > 1 ? "s" : "")+" could be generated by all widely used software libraries (we cannot rule out any library completely).");
                }

            }

            $("#resultsKeys").html("");
            $("#resultsKeysTogether").html("");
            // if (sameSourceMultiKeysResults === true) {
                var lines = [];
                lines = lines.concat(['<div style="overflow-y: hidden; overflow-x: auto;">', '<table class="table table-condensed table-bordered" style="margin-bottom: 0;">', '  <thead><tr>']);
                //Header columns
                data.containerResults.forEach(function (res) {
                    lines = lines.concat([
                        '<th style="white-space: nowrap">',
                        '  <span data-toggle="tooltip" title="tooltip-tmp">Group ' + res.group + '</span>',
                        '</th>'
                    ]);
                });
                lines = lines.concat(['  </tr>', '  </thead>', '  <tbody>', '  <tr>']);
                //Body columns
                if (data.containerResults === null) {
                    //TODO
                }
                else {
                    data.containerResults.forEach(function (res) {
                        lines = lines.concat([
                            '<td style="white-space: nowrap">',
                            '  ' + (res.value === null ? 'not possible' : (res.value * 100).toFixed(2) + " %"),
                            '</td>'
                        ]);
                    });
                }
                lines = lines.concat(['  </tr>', '  </tbody>', '  </table>', '</div>']);

                //Add key result table to the results
                var fixture = $(lines.join("\n"));
                $("#resultsKeysTogether").append(fixture);
            // }
            // else {
                //Construct table of results for each key
                data.classifiedKeys.forEach(function (key) {
                    //Identification of the key
                    var lines = [];
                    lines = lines.concat([
                        '<table class="table table-condensed" style="margin-bottom: 0;">',
                        '  <tbody><tr><td style="border: 0;">',
                        '<b>Key identification:</b> <i>' + key.identification + '</i>',
                        '</td>'
                    ]);
                    if (key.rsaKey !== null) {
                        lines = lines.concat([
                            '<td style="border: 0;width: 140px;text-align: right;"><b>Key length:</b>' + key.rsaKey.modulusBitLen + '</td>',
                            '<td style="border: 0;width: 130px;text-align: right;"><b>Exponent:</b>' + key.rsaKey.exponent + '</td>'
                        ]);
                    }
                    lines = lines.concat(['</tr></tbody></table>']);

                    lines = lines.concat(['<div style="overflow-y: hidden; overflow-x: auto;">', '<table class="table table-condensed table-bordered" style="margin-bottom: 0;">', '  <thead><tr>']);
                    //Header columns
                    key.orderedResults.forEach(function (res) {
                        lines = lines.concat([
                            '<th style="white-space: nowrap">',
                            '  <span data-toggle="tooltip" title="tooltip-tmp">Group ' + res.group + '</span>',
                            '</th>'
                        ]);
                    });
                    lines = lines.concat(['  </tr>', '  </thead>', '  <tbody>', '  <tr>']);
                    //Body columns
                    if (key.rsaKey === null) {
                        lines = lines.concat(['<td colspan="' + key.orderedResults.length + '" style="text-align: center;"><b>NO RSA KEY FOUND</b></td>']);
                    }
                    else {
                        key.orderedResults.forEach(function (res) {
                            lines = lines.concat([
                                '<td style="white-space: nowrap">',
                                '  ' + (res.value === null ? 'not possible' : (res.value * 100).toFixed(2) + " %"),
                                '</td>'
                            ]);
                        });
                    }
                    lines = lines.concat(['  </tr>', '  </tbody>', '  </table>', '</div>']);

                    //Add key result table to the results
                    var fixture = $(lines.join("\n"));
                    $("#resultsKeys").append(fixture);
                });
            // }
            $("#tableOfGroups tbody").html("");
            for (var group in groups[typeFlag]) {
                if (groups[typeFlag].hasOwnProperty(group)) {
                    $("#tableOfGroups tbody").append("<tr><td>Group " + group + "</td><td>" + groups[typeFlag][group].join(", ") + "</td></tr>");
                }
            }

            $('#details-tab').removeClass('disabled');
            $('.onlyForSomeKeys div.card').show();
        }
        $('#results-tab').removeClass('disabled');
        $("#results-tab").popover('enable');
        $("#results-tab").popover('show');
        setTimeout(function () {
            $("#results-tab").popover('hide');
            $("#results-tab").popover('disable');
        }, 3000);
        // $("#results-tab").tab("show");
    }

    function clearForm() {
        progressBar.style.display = "none";
        droppedFiles = false;
        $(textarea).val('');
        showFiles();
    }

    function processInput() {
        if ($(textarea).val().trim() == "" && droppedFiles === false) {
            return;
        }
        progressBar.style.display = "block";

        var ajaxData = new FormData(form);
        ajaxData.append("keys", $(textarea).val());
        if (droppedFiles) {
            Array.prototype.forEach.call(droppedFiles, function (file) {
                ajaxData.append("files[]", file);
            });
        }

        // ajax request
        var ajax = new XMLHttpRequest();
        ajax.open('POST', 'api/classify', true);

        ajax.onload = function () {
            if (ajax.status >= 200 && ajax.status < 400) {
                var response = JSON.parse(ajax.responseText);
                if (response.code !== 200) {
                    $("#errorModalMessage").text(response.error);
                    $("#errorModal").modal("show");
                    return;
                }

                if (response.uniqueKeys === 0 && firstResults) {
                    progressBar.style.display = "none";
                }

                var typeFlag = $('input[name=type_flag]:checked', '#keysForm').val();
                if (typeFlag !== "sw" && typeFlag !== "hw") {
                    typeFlag = "both";
                }
                if (groups[typeFlag] == null) {
                    var ajaxGroups = new XMLHttpRequest();
                    ajaxGroups.open('GET', 'api/groups?type_flag='+typeFlag, true);
                    ajaxGroups.onload = function () {
                        groups[typeFlag] = JSON.parse(ajaxGroups.responseText);
                        progressBar.style.display = "none";
                        showResults(response, false, typeFlag);
                        firstResults = false;
                    };
                    ajaxGroups.onerror = function () {
                        progressBar.style.display = "none";
                        alert('An error occurs. Please, try it later again!');
                    };
                    ajaxGroups.send(ajaxData);
                }
                else {
                    progressBar.style.display = "none";
                    showResults(response, false, typeFlag);
                    firstResults = false;
                }

                // if (response.uniqueKeys > 1) {
                //     $("#optionModalNumOfKeys").text(response.uniqueKeys);
                //     $("#optionModal").modal("show");
                //     lastResponse = response;
                // }
                // else {
                //     showResults(response, false);
                // }
                // var data = JSON.parse( ajax.responseText );
                // form.classList.add(response.code == 200 ? 'is-success' : 'is-error');
                // if( !data.success ) errorMsg.textContent = data.error;
            }
            else {
                progressBar.style.display = "none";
                $("#errorModalMessage").text('An error occurs. Please, try it later again!');
                $("#errorModal").modal("show");
            }
        };

        ajax.onerror = function () {
            alert('An error occurs. Please, try it later again!');
        };

        ajax.send(ajaxData);
    }

    function initSendingProcess() {
        if ($(textarea).val().trim() == "" && droppedFiles === false) {
            return;
        }

        progressBar.style.display = "block";
        clearTimeout(timer);
        timer = setTimeout(function () {
            progressBar.style.display = "none";
            processInput();
        }, 1000);
    }

    function showFiles() {
        initSendingProcess();

        var filesMsg = document.querySelector('.drag-and-drop-files');
        if (droppedFiles === false) {
            filesMsg.style.display = "none";
            return;
        }
        filesMsg.style.display = "block";
        filesMsg.querySelector('span').textContent = droppedFiles.length > 1 ? ("You inserted " + droppedFiles.length + " files.") : droppedFiles[0].name;
    }

    $('input[name=type_flag]').change(function () {
        initSendingProcess();
    });

    $(textarea).on('keyup paste', function () {
        initSendingProcess();
    });

    $(textarea).on('keyup paste', function () {
        initSendingProcess();
    });

    $("#removeFiles").click(function () {
        droppedFiles = false;
        showFiles();
    });

    $("#goToInsert").click(function () {
        $("#classify-tab").tab("show");
    });

    $("#goToDetails").click(function () {
        $("#details-tab").tab("show");
    });

    $("#insertOpenSSLKeys").click(function () {
        $.get( "keys/openssl.txt", function( data ) {
            var actualValue = $(textarea).val();
            if (actualValue != "") {
                actualValue = actualValue + "\n";
            }
            actualValue = actualValue + data;
            $(textarea).val(actualValue);
            initSendingProcess();
        });
    });

    $("#insertMbedtls").click(function () {
        $.get( "keys/mbedtls.txt", function( data ) {
            var actualValue = $(textarea).val();
            if (actualValue != "") {
                actualValue = actualValue + "\n";
            }
            actualValue = actualValue + data;
            $(textarea).val(actualValue);
            initSendingProcess();
        });
    });

    window.onscroll = function() {scrollFunction()};

    function scrollFunction() {
        if (document.body.scrollTop > 20 || document.documentElement.scrollTop > 20) {
            document.getElementById("scroolToTopButton").style.display = "block";
        } else {
            document.getElementById("scroolToTopButton").style.display = "none";
        }
    }

    $("#scroolToTopButton").click(function () {
        document.body.scrollTop = 0; // For Safari
        document.documentElement.scrollTop = 0; // For Chrome, Firefox, IE and Opera
    });

    'use strict';

    ;(function (document, window, index) {
        // feature detection for drag&drop upload
        var isAdvancedUpload = function () {
            var div = document.createElement('textarea');
            return (('draggable' in div) || ('ondragstart' in div && 'ondrop' in div)) && 'FormData' in window && 'FileReader' in window;
        }();

        // drag&drop files if the feature is available
        if (isAdvancedUpload) {
            form.classList.add('has-advanced-upload'); // letting the CSS part to know drag&drop is supported by the browser

            ['drag', 'dragstart', 'dragexit', 'dragend', 'dragover', 'dragenter', 'dragleave', 'drop'].forEach(function (event) {
                textarea.addEventListener(event, function (e) {
                    // preventing the unwanted behaviours
                    e.preventDefault();
                    e.stopPropagation();
                });
            });
            ['dragenter', 'drag'].forEach(function (event) {
                textarea.addEventListener(event, function () {
                    textarea.classList.add('dragAndDropUpload');
                    textarea.classList.remove('dragAndDropReady');
                    textarea.classList.remove('dragAndDropDefault');
                });
            });
            ['dragexit', 'dragleave'].forEach(function (event) {
                textarea.addEventListener(event, function () {
                    textarea.classList.remove('dragAndDropUpload');
                    textarea.classList.remove('dragAndDropReady');
                    textarea.classList.add('dragAndDropDefault');
                });
            });
            textarea.addEventListener('drop', function (e) {
                textarea.classList.remove('dragAndDropUpload');
                textarea.classList.remove('dragAndDropReady');
                textarea.classList.add('dragAndDropDefault');
                for (var i = 0; i < e.dataTransfer.files.length; i++) {
                    if (e.dataTransfer.files[i].size >= 1024*1024) {
                        $("#errorModalMessage").text('Dropped file ' + e.dataTransfer.files[i].name + ' is larger than 1 MB.');
                        $("#errorModal").modal("show");
                        return;
                    }
                }
                droppedFiles = e.dataTransfer.files; // the files that were dropped
                showFiles();
            });
        }
    }(document, window, 0));
});
