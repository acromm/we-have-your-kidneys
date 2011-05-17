<?php
/**
 * Identify user
 *
 * Finds them based on Cookie; then sets Cookie for +10 years
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

define('COOKIE_NAME',   'userId');
define('COOKIE_DOMAIN', '.wehaveyourkidneys.com');

// identify
$userUuid = isset($_COOKIE[COOKIE_NAME]) ? $_COOKIE[COOKIE_NAME] : NULL;
if ($userUuid === NULL || !uuid_is_valid($_COOKIE[COOKIE_NAME]))
{
    $userUuid = uuid_create(UUID_TYPE_RANDOM);
}
setcookie(
        COOKIE_NAME,
        $userUuid,
        strtotime('+10 years'),
        '/',
        COOKIE_DOMAIN
        );

// we have set of global; userUuid - fantastic