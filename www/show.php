<?php
/**
 * Show segments that user is in
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
 * along with We Have Your Kidneys.  If not, see <http://www.gnu.org/licenses/>.
 */

include_once dirname(__FILE__) . '/../thirdParty/phpcassa/columnfamily.php';
include_once dirname(__FILE__) . '/../lib/identify.php';

header('Content-Type: application/json');

try {
    // get
    $pool = new ConnectionPool('whyk', array('localhost'));
    $users = new ColumnFamily($pool, 'users');
    // @todo this only gets first 100!
    $segments = $users->get($userUuid);

    echo json_encode(array_keys($segments));
} catch (cassandra_NotFoundException $e) {
    echo json_encode('You have escaped being kidneyed, so far.');
} catch (Exception $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode($e->getMessage());
}
