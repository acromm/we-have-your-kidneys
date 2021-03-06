# Ad Network Demo

This code was written to provide a demonstration of DataStax's Brisk at the
[May 2011 Cassandra London meetup](http://www.meetup.com/Cassandra-London/events/16643691/).
It uses the [phpcassa Cassandra client library for PHP](https://github.com/thobbs/phpcassa).

You can [view a podcast of the talk here](http://skillsmatter.com/podcast/nosql/cassandra-may-meetup/js-1775 "Podcast on SkillsMatter website")
and [browse the slides here](http://www.slideshare.net/davegardnerisme/cassandra-hadoop-brisk).

It was then updated in October 2011 for a presentation at the NoSQL Exchange
2011. This time we added in some extra features for tracking impressions and
clicks and actually _recommending_ ads!

## Features

 - Powers wehaveyourkidneys.com
 - Uses Cookie-based identification system for each user
 - Real-time access: pixel / API for adding a user to a segment with
   optional expiry time (powered by Cassandra's "expiring columns" feature)
 - Real-time access: API for viewing what segments you have been added to
 - Real-time access: track time-based impressions and clicks for ads to show
   CTR over time, including per-segment
 - Real-time access: track overall performance of each segment for an ad
 - Batch analytics: Hive query to count the number of users in each segment
 - Batch analytics: Hive query to calculate the mean and standard deviation of
   the number of segments users belong to

## Usage

#### API to add a user to a segment

    http://wehaveyourkidneys.com/add.php?segment=<segmentCode>&expires=<numberOfSeconds>

Where:
 - segmentCode must be alphanumeric (a-zA-Z0-9)
 - numberOfSeconds will cause this user to be automatically removed from this
   segment after this time has elapsed (optional)

There is also a pixel version for using in img tags.

    http://pixel.wehaveyourkidneys.com/add.php?segment=<segmentCode>&expires=<numberOfSeconds>

#### API to view your segments

    http://wehaveyourkidneys.com/show.php

## Hive queries

One of the things that excites me about Brisk* is the ease with which you can
analyse data in Cassandra. Brisk provides Hive support for Cassandra (an
SQL-like interface for map reduce jobs). Brisk allows you to both read and
write data from Cassandra. During the talk I demonstrated the following
queries.

To run my queries, I created a Hive external table for my user ColumnFamily.
This used to be a requirement; however the latest version of Brisk will 
automatically hook up to any Cassandra CFs. However! What it won't do 
automatically is cast to nice column names (via the "mapping" parameter below).
Hence it is sometimes handy to create an external table with a new name.

    USE whyk;

    CREATE EXTERNAL TABLE tempUsers
    (userUuid string, segmentId string, value string)
    STORED BY 'org.apache.hadoop.hive.cassandra.CassandraStorageHandler'
    WITH SERDEPROPERTIES (
        "cassandra.columns.mapping" = ":key,:column,:value",
        "cassandra.cf.name" = "users"
        );

http://www.datastax.com/docs/1.0/datastax_enterprise/about_hive#reference-serdeproperties-and-tblproperties

I could then count up the number of users in each segment:

    SELECT segmentId, count(1) AS total
    FROM tempUsers
    GROUP BY segmentId
    ORDER BY total DESC;

I could also calculate the mean average and standard deviation of the number
of segments that users belong to:

    SELECT avg(num), stddev_samp(num)
    FROM (
        SELECT count(1) AS num
        FROM tempUsers
        GROUP BY userUuid
         ) tmp;

* NOTE: Brisk has now been dropped by DataStax in favour of their new 
"DataStax Enterprise" edition. This is a bit of a pain, however there are some
options. A "pimped fork" is [https://github.com/steeve/brisk](currently
maintained by steeve). Another option would be to use bog standard Cassandra
and then get the Hive driver working with a standard Hadoop install. With this
setup you could still execute Hive directly against Cassandra, but the results
would be stored in HDFS - hence you'd need a normal Hadoop install.

Finally, you can just go with DataStax Enterprise. There is a version available
specifically for startups, and a free trial for 30 days.

http://www.datastax.com/download


## Installation

I went for an Ubuntu Lucid Amazon box with compiled Brisk.

https://github.com/steeve/brisk

    sudo apt-get update
    sudo apt-get install git-core ant openjdk-6-jdk libmaven-compiler-plugin-java

    git clone git://github.com/steeve/brisk.git
    cd brisk
    ant
    ./bin/brisk cassandra -t

    sudo apt-get install apache2 php5 php-pear php5-dev uuid-dev
    cd /var/www
    git clone git://github.com/davegardnerisme/we-have-your-kidneys.git
    cd we-have-your-kidneys
    ln -s /var/www/we-have-your-kidneys/vhost/wehaveyourkidneys.com.vhost \
        /etc/apache2/sites-available/wehaveyourkidneys.com.vhost
    sudo a2ensite wehaveyourkidneys.com.vhost

    git submodule init
    git submodule update

    sudo pecl install uuid
    echo 'extension=uuid.so' > /etc/php5/conf.d/uuid.ini
    sudo service apache2 reload

## Some notes on the project design

This project has been written to try to make it obvious what Cassandra commands
are being executed. Things like DRY (Don't Repeat Yourself) have been ignored.
The idea is that any given file should be easy to read purely in terms of how
it reads or writes to Cassandra.

I choose PHP because I am most familiar with it. I am not suggesting it is the
most suitable language for this kind of application.

Segments are limited to alphanumeric and minus signs; in this way we can use
other special characters when constructing composite keys (for row / column 
names). Specifically, we prepend segment names with **seg:** and we use **|**
to split up composite column names.

