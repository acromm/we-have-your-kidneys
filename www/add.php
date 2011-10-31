<?php
/**
 * Add user to segment
 *
 * GET params:
 *
 *  - segment   Required; the segment to add to - validated by [a-zA-Z0-9]
 *  - expires   Optional; a number of seconds (integer) after which the user
 *              should be removed from this segment
 *
 * @author Dave Gardner <dave@cruft.co>
 *
 * This file is part of We Have Your Kidneys.
 *
 * We Have Your Kidneys is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * We Have Your Kidneys is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with We Have Your Kidneys.  If not, see <http://www.gnu.org/licenses/>. *
 */

include_once dirname(__FILE__) . '/../thirdParty/phpcassa/columnfamily.php';
include_once dirname(__FILE__) . '/../lib/identify.php';

try {
    // segment
    $segment = isset($_GET['segment']) ? $_GET['segment'] : NULL;

    // expires
    $expires = isset($_GET['expires']) ? $_GET['expires'] : NULL;

    // segment needed; expires must be int
    if ($segment === NULL || !preg_match('/^[a-zA-Z0-9]+$/', $segment)) {
        throw new Exception('Invalid "segment" param; need [a-zA-Z0-9]');
    }
    if ($expires !== NULL && !ctype_digit($expires)) {
        throw new Exception('Invalid "expires" param; need integer number of seconds');
    }
    
    // prepend segment name with "seg:"
    $segment = "seg:$segment";

    // store
    $pool = new ConnectionPool('whyk', array('localhost'));
    $users = new ColumnFamily($pool, 'users');
    $segments = new ColumnFamily($pool, 'segments');
    $users->insert(
            $userUuid,
            array($segment => 1),
            NULL,    // default TS
            $expires
            );
    $segments->insert(
            $segment,
            array($userUuid => 1),
            NULL,    // default TS
            $expires
            );

    // return as pixel?
    if (isset($_GET['pixel'])
            || strpos($_SERVER['HTTP_HOST'], 'pixel') !== FALSE) {
        header('Content-Type: image/gif');
        echo base64_decode(
                'R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw=='
                );
    } else {
        header('Content-Type: application/json');
        echo json_encode('OK');
    }
} catch (Exception $e) {
    header('HTTP/1.1 500 Internal Server Error');
    header('Content-Type: application/json');
    echo json_encode($e->getMessage());
}
