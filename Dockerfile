FROM php:7.2-cli

RUN mkdir /opt/media-organizer
WORKDIR /opt/media-organizer
COPY . .
