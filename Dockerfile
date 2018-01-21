FROM php:7.2-cli

RUN mkdir /opt/media-organizer
WORKDIR /opt/media-organizer
COPY . .


RUN chmod 777 /opt/media-organizer




RUN chmod 755 /opt/media-organizer


