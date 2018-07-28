Plex-TV-Analyzer
================

Plex-TV-Analyzer is a simple web page that will analyze a television show in your Plex library and tell you which episodes you are missing. Written as a web application. Must have PHP installed to use

Modifications by daeks
================

Installation: Modify and rename config.example.php to config.php

- Added PLEX Token support (see config.example.php)
- Added more layouts to display missing episodes only
- Added more statistics to selectlist to missing/total episodes, show status (C=Continueing, E=Ended) and PLEX section name
- Ended shows with no missing episodes will be removed after first lookup (information is stored at /cache/finished)
- Ended shows with missing episodes can be ignored (information is stored at /cache/ignore) - To unignore delete file in /cache/ignore

================

The MIT License (MIT)

Copyright (c) 2013 Taylor Hakes

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
