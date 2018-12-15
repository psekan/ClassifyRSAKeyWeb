var droppedFiles = false;
var timer;
var firstResults = true;
var groups = null;

$(document).ready(function () {
    var form = document.querySelector('#keysForm');
    var textarea = form.querySelector('textarea');
    var progressBar = document.querySelector('.nav-progress-item');

    function showResults(data, sameSourceMultiKeysResults) {
        if (data.correctKeys === 0) {
            $("#foundRSAKeys").html("We have not found any RSA public key.");
            $('#details-tab').addClass('disabled');
            $('.onlyForSomeKeys').hide();
        }
        else {
            if (data.correctKeys === 1) {
                $("#foundRSAKeys").html("We have found <b>one</b> RSA public key.");
            }
            else {
                $("#foundRSAKeys").html("We have found <b>"+data.correctKeys+"</b> RSA public keys.");
            }
            if (data.containerResults[0]['value'] != null) {
                var mostPropGrop = data.containerResults[0]['group'];
                if (groups[mostPropGrop].length > 1) {
                    $("#mostProbableSource").html("The most probable sources are <b>" + groups[mostPropGrop].join(", ") + "</b>.");
                    $("#resultsAccuracy").html("We are <b>" + (data.containerResults[0]['value']*100).toFixed(0) + " %</b> sure, that your keys were generated by these sources.");
                }
                else {
                    $("#mostProbableSource").html("The most probable source is <b>" + groups[mostPropGrop][0] + "</b>.");
                    $("#resultsAccuracy").html("We are <b>" + (data.containerResults[0]['value']*100).toFixed(0) + " %</b> sure, that your keys were generated by this source.");
                }

                var canBeFromOpenssl = true;
                var canBeFromOpensslFIPS = true;
                var canBeFromMicrosoft = true;
                var canBeFromGroup12 = true;
                data.containerResults.forEach(function (obj) {
                    if (obj['value'] === null) {
                        if (obj['group'] === 'VI') canBeFromOpenssl = false;
                        if (obj['group'] === 'XI') canBeFromMicrosoft = false;
                        if (obj['group'] === 'XII') canBeFromGroup12 = false;
                        if (obj['group'] === 'XIII') canBeFromOpensslFIPS = false;
                    }
                });

                if (!canBeFromOpenssl || !canBeFromMicrosoft || !canBeFromGroup12 || !canBeFromOpensslFIPS) {
                    var arr = [];
                    if (!canBeFromOpenssl) arr.push("OpenSSL");
                    if (!canBeFromMicrosoft) arr.push("Microsoft implementations, Bouncy Castle 1.54");
                    if (!canBeFromGroup12) arr.push("mbedTLS, OpenJDK, FlexiProvider, Bouncy Castle 1.53");
                    if (!canBeFromOpenssl) arr.push("OpenSSL FIPS, PGPSDK 4");
                    $("#negativeResults").html("We are sure that your key"+(groups[mostPropGrop].length > 1 ? "s" : "")+" could not be generated by these software libraries: "+arr.join(", ")+".");
                }
                else {
                    $("#negativeResults").html("Your key"+(groups[mostPropGrop].length > 1 ? "s" : "")+" could be generated by all widely used software libraries (we cannot rule out any library completely).");
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
            for (var group in groups) {
                if (groups.hasOwnProperty(group)) {
                    $("#tableOfGroups tbody").append("<tr><td>Group " + group + "</td><td>" + groups[group].join(", ") + "</td></tr>");
                }
            }

            $('#details-tab').removeClass('disabled');
            $('.onlyForSomeKeys').show();
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

                if (groups == null) {
                    var ajaxGroups = new XMLHttpRequest();
                    ajaxGroups.open('GET', 'api/groups', true);
                    ajaxGroups.onload = function () {
                        groups = JSON.parse(ajaxGroups.responseText);
                        progressBar.style.display = "none";
                        showResults(response, false);
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
                    showResults(response, false);
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
                droppedFiles = e.dataTransfer.files; // the files that were dropped
                showFiles();
                textarea.classList.remove('dragAndDropUpload');
                textarea.classList.remove('dragAndDropReady');
                textarea.classList.add('dragAndDropDefault');
            });
        }
    }(document, window, 0));
});