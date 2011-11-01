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

    $cols = new columnIterator($ads, $adId, date('YmdH', strtotime('-2 hours')));
    
    foreach ($cols as $k => $v) {
        echo "$k . $v \n";
    }
    
    // @todo split out each segment vs overall
    // @todo maybe have overall segment as _overall so it's first in list
    // now we have the hourly data
    
    // get overall segment data to compare against for baseline
    // @todo maybe do a multiget?
    
    
} catch (Exception $e) {
    header('HTTP/1.1 500 Internal Server Error');
    header('Content-Type: application/json');
    echo json_encode($e->getMessage());
}
