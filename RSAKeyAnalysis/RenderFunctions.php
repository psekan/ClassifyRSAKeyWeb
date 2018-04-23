<?php
require_once __DIR__ . "/classification/RawTable.php";
require_once __DIR__ . "/classification/ClassificationContainer.php";

/**
 * @param \RSAKeyAnalysis\ClassificationTable $table
 * @param $key
 */
function drawKeyResult(\RSAKeyAnalysis\ClassificationTable $table, $keyInfo) {
    /** @var \RSAKeyAnalysis\RSAKey $key */
    $key = $keyInfo["key"];
    if ($key === null) {
        $values = array();
    }
    else {
        /** @var \RSAKeyAnalysis\ClassificationRow $row */
        $row = $table->classifyKey($key);
        $values = $row->getValues();
    }
    arsort($values);


    echo '<div class="col-md-12"><table class="table table-condensed" style="margin-bottom: 0;"><tbody><tr>';
    echo '<td style="border: 0;"><b>Key identification (first few characters of in ascii armor/web domain):</b> <i>' . $keyInfo["identification"] . '</i></td>';
    if ($key !== null) {
        echo '<td style="border: 0;width: 200px;text-align: right;"><b>Key length:</b> ' . strlen($key->getModulus()->toBits()) . '</td>';
        echo '<td style="border: 0;width: 160px;text-align: right;"><b>Exponent:</b> ' . $key->getExponent()->toString() . '</td>';
    }
    echo '</tr></tbody></table>';
    if (array_key_exists("ta", $keyInfo)) {
        echo '<div class="alert alert-info" style="padding: 2px 5px; margin: 0;">
                <span class="glyphicon glyphicon-info-sign" aria-hidden="true"></span>
                This key is hardest to attribute to a particular source library. Pick this one if you like to use the most anonymous key.
              </div>';
    }
    echo '<div style="overflow: auto; margin-bottom: 25px;"><table class="table table-condensed table-bordered" style="margin-bottom: 0;"><thead><tr>';
    $allGroups = array_merge(array(), $table->getGroupsNames());
    usort($allGroups, '\RSAKeyAnalysis\common\RomanNumber::comparator');
    foreach ($values as $group => $value) {
        echo '<th style="white-space: nowrap"><span data-toggle="tooltip" title="' . implode(", ", $table->getGroupSources($group)) . '">Group ' . $group . '</span></th>';
        $index = array_search($group, $allGroups);
        unset($allGroups[$index]);
    }
    foreach ($allGroups as $group) {
        echo '<th style="white-space: nowrap"><span data-toggle="tooltip" title="' . implode(", ", $table->getGroupSources($group)) . '">Group ' . $group . '</span></th>';
    }
    echo '</tr></thead><tbody><tr>';
    if ($key === null) {
        echo '<td colspan="' . count($table->getGroupsNames()) . '" style="text-align: center;"><b>NO RSA KEY FOUND</b></td>';
    }
    else {
        $allGroups = array_merge(array(), $table->getGroupsNames());
        usort($allGroups, '\RSAKeyAnalysis\common\RomanNumber::comparator');
        foreach ($values as $group => $value) {
            echo '<td>' . number_format(doubleval($value) * 100, 2) . ' %</td>';
            $index = array_search($group, $allGroups);
            unset($allGroups[$index]);
        }
        foreach ($allGroups as $group) {
            echo '<td style="white-space: nowrap">not possible</td>';
        }
    }
    echo '</tr></tbody></table></div></div>';
}

/**
 * @param \RSAKeyAnalysis\ClassificationTable $table
 * @param \RSAKeyAnalysis\ClassificationContainer $container
 */
function drawContainerResult(\RSAKeyAnalysis\ClassificationTable $table, \RSAKeyAnalysis\ClassificationContainer $container) {
    $row = $container->getRow();
    $values = $row->getValues();
    arsort($values);

    echo '<div class="col-md-12"><div style="overflow: auto; margin-bottom: 12px;"><table class="table table-condensed table-bordered" style="margin-bottom: 0"><thead><tr>';
    $allGroups = array_merge(array(), $table->getGroupsNames());
    usort($allGroups, '\RSAKeyAnalysis\common\RomanNumber::comparator');
    $first = true;
    foreach ($values as $group => $value) {
        echo '<th class="clickAble' . ($first ? ' success' : '') . '" data-group="' . $group . '" style="white-space: nowrap"><span data-toggle="tooltip" title="' . implode(", ", $table->getGroupSources($group)) . '">Group ' . $group . '</span></th>';
        $index = array_search($group, $allGroups);
        unset($allGroups[$index]);
        $first = false;
    }
    foreach ($allGroups as $group) {
        echo '<th style="white-space: nowrap" class="clickAble" data-group="' . $group . '"><span data-toggle="tooltip" title="' . implode(", ", $table->getGroupSources($group)) . '">Group ' . $group . '</span></th>';
    }
    echo '</tr></thead><tbody><tr>';
    $allGroups = array_merge(array(), $table->getGroupsNames());
    usort($allGroups, '\RSAKeyAnalysis\common\RomanNumber::comparator');
    $first = true;
    foreach ($values as $group => $value) {
        echo '<td class="clickAble' . ($first ? ' success' : '') . '" data-group="' . $group . '">' . number_format(doubleval($value) * 100, 2) . ' %</td>';
        $index = array_search($group, $allGroups);
        unset($allGroups[$index]);
        $first = false;
    }
    foreach ($allGroups as $group) {
        echo '<td style="white-space: nowrap" class="clickAble" data-group="' . $group . '">not possible</td>';
    }
    echo '</tr></tbody></table></div></div>';
}