# Railtracker

Tracks user interactions with your site including, page views, logins, custom actions, etc.

## Requests/Responses

Railtracker looks at incoming server responses and stores information about it in our database. 
It stores the following for all requests:

- user id
- cookie id (for anonymous visitors)
- url
- laravel route
- device info
- agent info
- request method (PUT, PATCH, etc)
- referring url
- language
- ip
- date

It stores the following for all responses:

- request id
- status code returned
- response duration (how long it took the server to respond)
- date

## Exceptions

Railtracker also stores any error/exception information that happens while the server processes the request:

- request id
- exception code
- exception line
- exception class
- exception file
- exception message
- exception trace


## Media Playback

The last part of railtracker is media playback tracking. It tracks how many seconds of any given content that a user watches/consumes. The data it stores looks like this:

- media id (usually a vimeo video id or youtube video id)
- user id
- seconds played
- current second (where the user currently is in the video)
- date

## Final Note

Railtracker is purely a tool for storing the above information, it does not process or analyze the information in any way.