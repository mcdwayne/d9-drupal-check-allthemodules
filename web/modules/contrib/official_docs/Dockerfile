# Use an official PHP runtime as a parent image
FROM composer:latest

# Set the working directory to /official-docs
WORKDIR /official-docs

# Copy the current directory contents into the container at /official-docs
ADD . /official-docs

# Install apt packages.
RUN apk add --no-cache asciidoc xmlto