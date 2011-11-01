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
 * [YmdH] = {
 *      clicks,
 *      impressions,
 *      ctr,
 *      index,      (against baseline ctr for everything)
 *      segments: [
 *              {
 *                  'segment': 'foo',
 *                  clicks,
 *                  impressions,
 *                  ctr,
 *                  index       (against baseline for this segment)
 *              }
 *          ]
 *      }
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

    $timeBuckets = array();
    
    $cols = new columnIterator($ads, $adId, date('YmdH', strtotime('-2 hours')));
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

        if (!isset($timeBuckets[$k])) {
            $timeBuckets[$stamp] = array(
                'clicks'      => 0,
                'impressions' => 0,
                'ctr'         => 0,
                'index'       => 0,
                'segments'    => array()
                );
        }
        
        if ($segment === '_all') {
            $timeBuckets[$stamp][$action] = $val;
        } else {
            if (!isset($timeBuckets[$stamp]['segments'][$segment])) {
                $timeBuckets[$stamp]['segments'][$segment] = array(
                    'click'      => 0,
                    'impression' => 0,
                    'ctr'        => 0,
                    'index'      => 0
                    );
            }
        }
    }
    
    // @todo split out each segment vs overall
    // @todo maybe have overall segment as _overall so it's first in list
    // now we have the hourly data
    
    // get overall segment data to compare against for baseline
    // @todo maybe do a multiget?
    
    header('Content-Type: application/json');
    echo json_encode($timeBuckets);
    
} catch (Exception $e) {
    header('HTTP/1.1 500 Internal Server Error');
    header('Content-Type: application/json');
    echo json_encode($e->getMessage());
}
