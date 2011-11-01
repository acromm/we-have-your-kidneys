<?php
/**
 * Show ad performance over time
 *
 * GET params:
 * 
 *  - ad        Required; the unique ad identifier
 * 
 * We will get overall performance of ad against baseline
 * 
 * 
 * 
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

/**
 * 'click':      0
 * 'impression': 0
 * 'ctr'         0
 * 
 * segments: [
 *     foo: {
 *         click:      0
 *         impression: 0
 *         ctr:        0
 *         index:      0  <-- against the ad baseline
 *         }
 * ]
 * 
 * time: [
 *     stamp: {
 *         click:      0
 *         impression: 0
 *         ctr:        0
 *         index:      0  <-- against the ad baseline
 *     }
 * ]
 * 
 */

include_once dirname(__FILE__) . '/../thirdParty/phpcassa/columnfamily.php';
include_once dirname(__FILE__) . '/../lib/identify.php';
include_once dirname(__FILE__) . '/../lib/ads.php';
include_once dirname(__FILE__) . '/../lib/columnIterator.php';

try {
    // ad#
    $adId = isset($_GET['ad']) ? $_GET['ad'] : NULL;

    // ad must exist
    if (!isset($adDefinitions[$adId])) {
        throw new Exception('Invalid "ad" param; not a valid ad');
    }

    $pool = new ConnectionPool('whyk', array('localhost'));

    $segments = new ColumnFamily($pool, 'segments');
    $ads = new ColumnFamily($pool, 'ads');

    $data = array(
        'click'      => 0,
        'impression' => 0,
        'ctr'        => 0,
        'segments'   => array(
            
            ),
        'time'       => array(
            )
        );
    
    $cols = new columnIterator($ads, $adId, date('YmdH', strtotime('-1 day')));
    foreach ($cols as $col => $val) {
        // col == our composed column name; val = our count
        
        // parse column
        $parts = explode('|', $col);
        if (count($parts) !== 3) {
            continue;
        }
        
        $stamp = $parts[0];
        $segment = $parts[1];
        $action = $parts[2];
        
        if ($segment == '_all') {
            // overall
            $data[$action] += $val;
            
            // add time bucket, if needed
            if (!isset($data['time'][$stamp])) {
                $data['time'][$stamp] = array(
                    'click'       => 0,
                    'impression'  => 0,
                    'ctr'         => 0,
                    'index'       => 0
                    );
            }
            $data['time'][$stamp][$action] += $val;
            
        } else {
            // segment
            if (!isset($data['segments'][$segment])) {
                $data['segments'][$segment] = array(
                    'click'       => 0,
                    'impression'  => 0,
                    'ctr'         => 0,
                    'index'       => 0
                    );
            }
            $data['segments'][$segment][$action] += $val;
        }
    }
    
    // work out CTRs and indexes
    $data['ctr'] = $data['impression'] > 0
            ? $data['click'] / $data['impression']
            : 0;
    
    foreach ($data['time'] as &$bucket) {
        $bucket['ctr'] = $bucket['impression'] > 0
            ? $bucket['click'] / $bucket['impression']
            : 0;
        $bucket['index'] = $data['ctr'] > 0
            ? ($bucket['ctr'] / $data['ctr']) * 100
            : 0;
    }
    foreach ($data['segments'] as &$bucket) {
        $bucket['ctr'] = $bucket['impression'] > 0
            ? $bucket['click'] / $bucket['impression']
            : 0;
        $bucket['index'] = $data['ctr'] > 0
            ? ($bucket['ctr'] / $data['ctr']) * 100
            : 0;
    }
    
    header('Content-Type: application/json');
    echo json_encode($timeBuckets);
    
} catch (Exception $e) {
    header('HTTP/1.1 500 Internal Server Error');
    header('Content-Type: application/json');
    echo json_encode($e->getMessage());
}
