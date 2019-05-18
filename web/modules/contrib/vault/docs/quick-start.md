# Quick Start Guide

The intended audience for this guide are evaluators wanting to understand how Drupal can leverage Vault. 

* You may not have used Vault before
* You have some familiarity with Key module
* You have some familiarity with Encrypt module
* You are familiar with using composer in Drupal projects
* You are familiar with Docker and Docker Compose

This guide _will not_:

* Give you a secure Vault service
* Explain details of operational best-practices

## Stack

This guide will use Docker Compose to run the required components. This code for the demo stack is located [here](https://github.com/nicksantamaria/drupal-vault/tree/8.x-1.x/demo).

Get this code onto your local machine and run `docker-compose up -d`.

## Vault - A Crash Course

* Vault is a single binary which runs as a service. 
* Vault runs on port `8200` by default 
* Vault almost always requires HTTPS (except when used in `-dev` mode, which is conveniently the case here)
* Vault has a CLI client, a web UI, and a RESTful API.
