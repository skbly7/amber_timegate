# -*- coding: utf-8 -*-
#
# This file is part of TimeGate.
# Copyright (C) 2016 CERN.
#
# TimeGate is free software; you can redistribute it and/or modify
# it under the terms of the Revised BSD License; see LICENSE file for
# more details.


"""Pytest configuration."""

from __future__ import absolute_import, print_function

import pytest


@pytest.fixture()
def app(tmpdir):
    """Initialize cache directory."""
    from timegate import application
    from timegate.cache import Cache
    return application.TimeGate(config=dict(
        HOST='http://localhost',
        BASE_URI='http://www.example.com/',
        CACHE_USE=True,
        CACHE_FILE=tmpdir.mkdir('cache').strpath,
    ))


@pytest.fixture()
def client(app):
    """Application fixture."""
    from timegate import application
    from werkzeug.test import Client
    from werkzeug.wrappers import BaseResponse
    return Client(app, BaseResponse)
