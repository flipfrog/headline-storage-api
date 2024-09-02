## Media Headlines Storage Backend Server

This repository is a backend server code for Media Headlines Storage System which is work in progress.
The system is that I am personally developing.

Frontend system of the system is also now developing, and is about to be revealed soon.

### What is Media Headlines Storage

I think to build the system following;
- Store Meta information such as title and headlines of Text media, Sound medias, Network Links.
- I want to connect each other to make my own collaborative filtering sort of environment.

### To Install
Run following commands to install.
```shell
git clone https://github.com/flipfrog/headline-storage-api.git
cd headline-storage-api
```
then, install sail environment.
```shell
composer install
cp .env.example .env
php artisan sail:install
```
choose a database system.

then, make up docker containers.
```shell
make up
```
waiting to start up docker containers.

then, migrate database tables.
```shell
make migrate
```

### Usage

If your operating system has make command, you can use a Makefile to control docker containers using make command.
- To control containers, `make up`, `make down`.
- To run migration, `make migrate`.
- To run shell `make sh`.
- To run test `make test`
