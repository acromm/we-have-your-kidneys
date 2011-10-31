<?php
/**
 * Record ad click
 *
 * GET params:
 * 
 *  - ad        Required; the unique ad identifier
 * 
 * We need to udpdate:
 * 
 * -> for capacity planning:
 *   ["segments"][<stamp>][<bucketId>]["click"] = #
 * -> for overall ad performance tracking:
 *   ["ads"][<adId>][<stamp>]["click"] = #
 * -> for ad performance by bucket:
 *   ["ads"][<adId>][<bucketId>]["click"] = #
 * 
 * Then we redirect via 307.
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
 * 
 */

include_once dirname(__FILE__) . '/../thirdParty/phpcassa/columnfamily.php';
include_once dirname(__FILE__) . '/../lib/identify.php';
include_once dirname(__FILE__) . '/../lib/ads.php';

try {
    // segment
    $adId = isset($_GET['ad']) ? $_GET['ad'] : NULL;
    
    // ad must exist
    if (!isset($adDefinitions[$adId])) {
        throw new Exception('Invalid "ad" param; not a valid ad');
    }

    $pool = new ConnectionPool('whyk', array('localhost'));

    // get user's segments
    $users = new ColumnFamily($pool, 'users');
    $userSegments = $users->get($userUuid);
    
    // update
    $segments = new ColumnFamily($pool, 'segments');
    $ads = new ColumnFamily($pool, 'ads');
    
    // 1. update counters based on the user's segments (one update per segment)
    foreach ($userSegments as $segment => $value) {
        $ads->add(
                $adId,          // row key
                'click',        // column
                1,              // increment
                $segment        // super-column
                );
        $segments->add(
                date('YmdH'),   // row key = hourly timestamp bucket
                'click',        // column
                1,              // increment
                $segment        // super-column
                );
    }
    
    // 2. update overall counters for this ad (for performance tracking)
    $ads->add(
            $adId,              // row key
            'click',            // column
            1,                  // increment
            $stamp              // super-column
            );

    // ---
    
    // redirect
    
    header('HTTP/1.1 307 Temporary Redirect');
    header('Content-Type: text/plain');
    header('Location: ' . $adDefinitions[$adId]['destination']);
    echo "Redirecting to {$adDefinitions[$adId]['destination']}...\n";
    
} catch (Exception $e) {
    header('HTTP/1.1 500 Internal Server Error');
    header('Content-Type: application/json');
    echo json_encode($e->getMessage());
}
