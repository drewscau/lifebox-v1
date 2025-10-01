<?php

// Additional scribe yaml files, that are manually created
return [
    'passport' => <<<YAML
- httpMethods:
    - POST
  uri: oauth/token
  metadata:
    groupName: Auth
    groupDescription: Authentication endpoint
    title: Laravel-Passport password token
    description: 'Generate a token with username/password'
    authenticated: false
  headers:
    Content-Type: application/json
    Accept: application/json
  urlParameters: []
  queryParameters: []
  bodyParameters:
    grant_type:
      name: grant_type
      description: 'password'
      required: true
      example: 'password'
      type: string
    client_id:
      name: client_id
      description: 'client_id'
      required: true
      example: 123
      type: int
    client_secret:
      name: client_secret
      description: 'client_secret'
      required: true
      example: '3aAjgeadqPSshB6h1ZB2Qhn8X3XXtBgnnF42pG9G'
      type: string
    username:
      name: username
      description: 'username'
      required: true
      example: 'test@lifebox.net.au'
      type: string
    password:
      name: password
      description: 'password'
      required: true
      example: 'secret'
      type: string
  responses:
    - status: 200
      description: 'After creating token from password'
      content: # Your response content can be an object, an array, a string or empty.
         {
           "token_type": "Bearer",
           "expires_in": 123456789,
           "access_token": "access-token-hash",
           "refresh_token": "refresh-token-hash"
         }
  responseFields: []

- httpMethods:
    - POST
  uri: oauth/token
  metadata:
    groupName: Auth
    groupDescription: Authentication endpoint
    title: Laravel-Passport refresh token
    description: 'Generate a token using a refresh-token'
    authenticated: false
  headers:
    Content-Type: application/json
    Accept: application/json
  urlParameters: []
  queryParameters: []
  bodyParameters:
    grant_type:
      name: grant_type
      description: 'refresh_token'
      required: true
      example: 'refresh_token'
      type: string
    client_id:
      name: client_id
      description: 'client_id'
      required: true
      example: 123
      type: int
    client_secret:
      name: client_secret
      description: 'client_secret'
      required: true
      example: '3aAjgeadqPSshB6h1ZB2Qhn8X3XXtBgnnF42pG9G'
      type: string
    refresh_token:
      name: refresh_token
      description: 'refresh_token'
      required: true
      example: 'def50200d370a8377a7b3e1403ea09f9ec045695417675feb09d88b23b0e...'
  responses:
    - status: 200
      description: 'After creating token from refresh_token'
      content: # Your response content can be an object, an array, a string or empty.
        {
          "token_type": "Bearer",
          "expires_in": 123456789,
          "access_token": "access-token-hash",
          "refresh_token": "refresh-token-hash"
        }
  responseFields: []
YAML,

];
