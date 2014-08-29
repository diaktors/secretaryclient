SecretaryClient
===================

This package comes with two things: 

- a cli tool to view/create notes
- a PHP client library for the communication with SecretaryAPI

So, the cli tool is like a demonstration of what can be done using the API of [Secretary](http://github.com/wesrc/secretary).


## Cli Tool

Call `./client.php` or `./client.php configure` directly. Enter the details of your api. Start viewing/adding notes after.

## Available Commands
- `./client.php configure`
- `./client.php create`
- `./client.php delete`
- `./client.php edit`
- `./client.php listNotes`
- `./client.php view`

## Client library

See code inside src/Client or the usage of it inside src/Command/*

Documentation will follow at some point.

