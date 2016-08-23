# -*- coding: utf-8 -*-
#
# This file is part of TimeGate.
# Copyright (C) 2014, 2015 LANL.
# Copyright (C) 2016 CERN.
#
# TimeGate is free software; you can redistribute it and/or modify
# it under the terms of the Revised BSD License; see LICENSE file for
# more details.

from __future__ import absolute_import, print_function

import logging
import StringIO
from datetime import datetime

from lxml import etree

from timegate.errors import HandlerError
from timegate.handler import Handler


class NaraHandler(Handler):

    def __init__(self):
        Handler.__init__(self)
        self.baseuri = "http://webharvest.gov/"
        congress_number = 109
        FIRST_YEAR = 2006
        THIS_YEAR = datetime.utcnow().year
        self.collections = ["peth04"]

        for i in range(FIRST_YEAR, THIS_YEAR, 2):
            self.collections.append("congress%sth" % congress_number)
            congress_number += 1

    def get_all_mementos(self, requri):
        # implement the changes list for this particular proxy
        changes = []

        for collection in self.collections:
            uri = self.baseuri + collection + "/*/" + requri
            dom = self.get_xml(uri, html=True)

            if dom:
                rlist = dom.xpath('//*[@class="mainBody"]')
                for td in rlist:
                    if len(td.getchildren()) > 0:
                        for a in td:
                            if a.tag == 'a':
                                loc = a.get('href')
                                if not loc.startswith(self.baseuri):
                                    if loc.startswith("/"):
                                        loc = self.baseuri + loc[1:]
                                    else:
                                        loc = self.baseuri + loc
                                dtstr = a.get('onclick').split("'")[1] + " GMT"

                                # if a.tail:
                                changes.append((loc, dtstr))

        return changes

    def get_xml(self, uri, html=False):
        """Retrieves the resource using the url, parses it as XML or HTML and
        returns the parsed dom object.

        :param uri: [str] The uri to retrieve
        :param headers: [dict(header_name: value)] optional http headers to
            send in the request.
        :param html: [bool] optional flag to parse the response.
        as HTML
        :return: [lxml_obj] parsed dom.
        """

        page = self.request(uri)
        try:
            page_data = page.content
            if not html:
                parser = etree.XMLParser(recover=True)
            else:
                parser = etree.HTMLParser(recover=True)
            return etree.parse(StringIO.StringIO(page_data), parser)
        except Exception as e:
            logging.error("Cannot parse XML/HTML from %s" % uri)
            raise HandlerError("Couldn't parse data from %s" % uri)
