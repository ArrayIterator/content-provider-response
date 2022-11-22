# CONTENT PROVIDER EXAMPLE

> ERROR KEY ON: `code`

- **EMPTY_CHANNEL_ID**: Channel ID Is Empty
- **EMPTY_CP_CHANNEL_ID**: Content Provider Channel ID Is Empty
- **INVALID_CHANNEL_ID**: Channel ID Is Invalid (nor integer)
- **INVALID_CP_CHANNEL_ID**: Content Provider  Channel ID Is Invalid (nor integer)
- **EMPTY_SUBSCRIPTION_PRICE**: Subscription Price Has empty
- **EMPTY_SUBSCRIPTION_PERIOD**: Subscription Period Has empty
- **EMPTY_SUBSCRIPTION_FREQUENCY**: Subscription Frequency Has empty
- **EMPTY_CREATOR_NAME**: Creator Name Has empty
- **EMPTY_STATUS**: Status Has empty
- **EMPTY_SLUG**: Slug Has empty
- **INVALID_CHANNEL_ID**: Channel ID is Invalid (nor integer)
- **INVALID_SUBSCRIPTION_PERIOD**: Subscription Period Invalid (nor integer)
- **INVALID_SUBSCRIPTION_FREQUENCY**: Subscription Frequency Invalid (nor integer)
- **INVALID_SUBSCRIPTION_PRICE**: Subscription Price Invalid (nor integer)
- **NOTFOUND_CHANNEL_ID**: Channel ID Not Found
- **MISMATCH_CHANNEL_ID**: Channel ID & Content Provider ID Mismatch (from CP Database)
- **EMPTY_ATTACHMENT**: Required Attachments Has empty (Both File & Cover image has empty)
- **EMPTY_CONTENT_ID**: Content ID Has Empty
- **NOTFOUND_CONTENT_ID**: Content ID Has Not Found

## NGINX ADD BASE PATH

```apacheconf
server {
    # block server ....
    listen 80;
    listen 443 ssl http2;
    error_log /path/to/error.log;
    # or use : access_log off;
    access_log /path/to/access/access.log main;
    # disable log not found
    log_notfound off;

    ssl_certificate /path/to/fullchain.pem;
    ssl_certificate_key /path/to/privkey.pem;

    location @proxy {
        # the proxy
        proxy_pass http://127.0.0.1:3000;
    }

    # .....
    # start here, insert before location /
    location ~ /content-provider(/|/?$) {
        # add CORS
        add_header Access-Control-Allow-Origin *;

        root /path/to/app/public;
        index index.php;

        # this for basepath
        fastcgi_param base_path /content-provider;

        # php listener
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param SCRIPT_NAME $fastcgi_script_name;
        fastcgi_index index.php;

        fastcgi_intercept_errors off;
        fastcgi_buffers 16 16k;
        fastcgi_buffer_size 32k;
        fastcgi_pass unix:/var/run/php/pool.sock;
        try_files /index.php$is_args$args =404;
        # or use
        # try_files /index.php$is_args$args @proxy;

    }

    location / {
        try_files @proxy =404;
    }
}
``