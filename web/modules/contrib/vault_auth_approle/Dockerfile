FROM previousnext/php:7.2-dev

WORKDIR /data

# Install git, curl and yamllint.
RUN apt-get update && \
    apt-get install --yes --force-yes python-pip && \
    pip install yamllint && \
    rm -rf /var/lib/apt/lists/* ~/.cache

# Latest version of jq.
RUN curl -s -L https://github.com/stedolan/jq/releases/download/jq-1.5/jq-linux64 -o /usr/local/bin/jq && \
    chmod +rx /usr/local/bin/jq

# Lint tool for terraform.
RUN curl -s -L https://github.com/wata727/tflint/releases/download/v0.5.4/tflint_linux_amd64.zip -o /tmp/tflint.zip && \
    cd /usr/local/bin && \
    unzip /tmp/tflint.zip && \
    rm /tmp/tflint.zip

# Composer tooling.
RUN composer global config minimum-stability dev && \
    composer global require \
        "drush/drush:^8" \
        "drupal/coder:^8.2.12" \
        "squizlabs/php_codesniffer:^2.9" \
        "dealerdirect/phpcodesniffer-composer-installer"

ENV PATH="${PATH}:/root/.composer/vendor/bin"

RUN mkdir -p ~/.ssh && \
    echo "Host git.drupal.org" >> ~/.ssh/config && \
    echo "  StrictHostKeyChecking no" >> ~/.ssh/config
