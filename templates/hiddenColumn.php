<?php

$html = '';

/**
 * Header
 */
if (!empty($headers)) {
    $html .= '<tr>';
    foreach ($headers as $aHeader) {
        $style = (strtolower($aHeader) === 'id') ? 'display: none;' : '';
        $html .= "<th style='{$style}'>{$aHeader}</th>";
    }
    $html .= '</tr>';
}

/**
 * Body
 */
if (!empty($rows)) {
    foreach ($rows as $row) {
        $html .= '<tr>';
        foreach ($row as $columnName => $column) {
            $style = (strtolower($columnName) === 'id') ? 'display: none;' : '';

            if (is_array($column)) {
                $content = '';
                foreach ($column as $aColumnKey => $aColumnValue) {
                    $content .= "{$aColumnKey} : {$aColumnValue} ";
                }

                $content = htmlspecialchars($content);

                $html .= "<td style='{$style}'>{$content}</td>";
            } else {
                $column = htmlspecialchars($column);

                $html .= "<td style='{$style}'>{$column}</td>";
            }
        }
        $html .= '</tr>';
    }
} else {
    // No result

    // To prevent XSS prevention convert user input to HTML entities
    $query = htmlentities($query, ENT_NOQUOTES, 'UTF-8');

    // there is no result - return an appropriate message.
    $html .= "<tr><td>There is no result for \"{$query}\"</td></tr>";
}

return $html;
