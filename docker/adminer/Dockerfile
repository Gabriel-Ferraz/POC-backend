FROM adminer:latest

USER root

RUN apk add --no-cache libaio libnsl libc6-compat wget unzip

# Oracle Instant Client 21.9 (mesma versão do app)
RUN mkdir -p /opt/oracle && cd /opt/oracle \
    && wget -q "https://download.oracle.com/otn_software/linux/instantclient/219000/instantclient-basiclite-linux.x64-21.9.0.0.0dbru.zip" \
    && wget -q "https://download.oracle.com/otn_software/linux/instantclient/219000/instantclient-sdk-linux.x64-21.9.0.0.0dbru.zip" \
    && unzip -q instantclient-basiclite-linux.x64-21.9.0.0.0dbru.zip \
    && unzip -q instantclient-sdk-linux.x64-21.9.0.0.0dbru.zip \
    && rm -f *.zip \
    && echo /opt/oracle/instantclient_21_9 > /etc/ld.so.conf.d/oracle-instantclient.conf || true \
    && ln -sf /opt/oracle/instantclient_21_9/libclntsh.so.21.1 /usr/lib/libclntsh.so

ENV LD_LIBRARY_PATH="/opt/oracle/instantclient_21_9"
ENV ORACLE_HOME="/opt/oracle/instantclient_21_9"

# Build OCI8 para a versão do PHP do adminer
RUN apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
    && echo "instantclient,/opt/oracle/instantclient_21_9" | pecl install oci8-3.3.0 \
    && docker-php-ext-enable oci8 \
    && apk del .build-deps

USER adminer
