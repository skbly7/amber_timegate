# Amber is an open source tool for websites to provide their visitors persistent routes to information.
# It automatically preserves a snapshot of every page linked to on a website, giving visitors a fallback
# option if links become inaccessible.
#
# If one of the pages linked to on this website were to ever go down, Amber can provide visitors with
# access to an alternate version. This safeguards the promise of the URL: that information placed online
# can remain there, even amidst network or endpoint disruptions.

"""Distributed Amber handler."""

from __future__ import absolute_import, print_function

import MySQLdb
import random
import logging
from datetime import datetime

from timegate.errors import HandlerError
from timegate.handler import Handler


class AmberHandler(Handler):

    def __init__(self):
        Handler.__init__(self)
        self.db = MySQLdb.connect(
            host="localhost",
            user="username",
            passwd="password",
            db="amber_timegate"
        )
        self.cursor = self.db.cursor()
        logging.basicConfig(filename='/tmp/temp.log')

    def get_all_mementos(self, uri_r):
        cursor = self.cursor
        query = 'SELECT CONCAT(url, "/amber/cache/", cache_id, "/"), timestamp ' \
                'FROM amber_urim urim LEFT JOIN amber_node n ON urim.node_id=n.id ' \
                'WHERE urir_id IN (SELECT id FROM amber_urir WHERE url = "{0}")'.format(uri_r)
        cursor.execute(query)
        result = cursor.fetchall()
        all_snapshots = []
        for snapshot in result:
            all_snapshots.append((snapshot[0], datetime.fromtimestamp(int(snapshot[1])).strftime('%Y-%m-%d %H:%M:%S')))
        if not len(all_snapshots):
            raise HandlerError("No snapshot found for queried URI-R.", status=404)
        logging.error(all_snapshots)
        return all_snapshots

    def get_memento(self, uri_r, req_datetime):
        all_snapshots = self.get_all_mementos(uri_r)
        return random.choice(all_snapshots)


