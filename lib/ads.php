<?php
/**
 * List of ads
 *
 * This stuff is a good candidate for RDBMS; there's not much of it, it is
 * changed relatively infrequently by a small number of people, we probably
 * want to be able to track who created/edited etc..
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

$adDefinitions = array(
    1 => array(
        'title'       => 'Cassandra London',
        'img'         => 'http://wehaveyourkidneys.com/i/cassandra-london.jpg',
        'destination' => 'http://www.meetup.com/Cassandra-London/events/36802872/'
        ),
    2 => array(
        'title'       => 'Things',
        'img'         => 'http://wehaveyourkidneys.com/i/things.jpg',
        'destination' => 'http://www.google.co.uk/search?q=things'
        ),
    3 => array(
        'title'       => 'Cassandra London',
        'img'         => 'http://wehaveyourkidneys.com/cassandra-london.jpg',
        'destination' => 'http://www.meetup.com/Cassandra-London/events/36802872/'
        )
    );


// we have set _another_ global $adDefinitions; this just gets better