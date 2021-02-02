FROM alpine:3.12

RUN apk add --no-cache git

RUN chmod +x entrypoint.sh

COPY entrypoint.sh /entrypoint.sh

ENTRYPOINT ["/entrypoint.sh"]