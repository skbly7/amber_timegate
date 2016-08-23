# -*- coding: utf-8 -*-
#
# This file is part of TimeGate.
# Copyright (C) 2014, 2015 LANL.
# Copyright (C) 2016 CERN.
#
# TimeGate is free software; you can redistribute it and/or modify
# it under the terms of the Revised BSD License; see LICENSE file for
# more details.

"""Croatian web archive proxy."""

from __future__ import absolute_import, print_function

import logging
import re
import urllib

from timegate.errors import HandlerError
from timegate.handler import Handler

baseuri = "http://haw.nsk.hr/json.php?"


class CrHandler(Handler):

    def __init__(self):
        Handler.__init__(self)

    def get_all_mementos(self, req_url):
        # implement the changes list for this particular proxy

        parameters = {}
        parameters['q'] = req_url
        parameters['subject'] = 'url'

        uri = baseuri + urllib.urlencode(parameters)
        try:
            jsonobj = self.request(uri).json()
        except Exception as e:
            logging.error("Cannot request API or parse json response: " + e)
            raise HandlerError("Cannot get API response.", 404)

        changes = []

        if int(jsonobj['availableHits']) == 0:
            return []

        tmid = jsonobj['hits'][0]['ID']
        tmuri = "http://haw.nsk.hr/publikacija/" + tmid

        try:
            data = self.request(tmuri).content
        except Exception as e:
            logging.error("Error requerying API: " + e)
            raise HandlerError("Cannot get API response.", 404)

        uriRegex = re.compile(r'<tr><td>[\d]*\.</td>.*</tr>')
        dtregex = re.compile('<td>\d\d\.\d\d\.\d\d\d\d[0-9\.:\s]*</td>')

        uris = re.findall(uriRegex, data)
        for u in uris:
            d = u.index("title")

            loc = "http://haw.nsk.hr/" + u[45:d - 2].lstrip('/')

            result = dtregex.search(u)
            if result:
                dtstr = result.group(0)
            dtstr = dtstr[4:-5]

            dtstr = dtstr[6:10] + dtstr[3:5] + dtstr[0:2] + \
                dtstr[11:19].replace(":", "") + " GMT"
            changes.append((loc, dtstr))

        return changes
