**Gas** is a JAV magnet crawler, written in PHP.

[简体中文](.github/README-zh-CN.md)

## Table of Contents

- [Sources](#sources)
- [Install](#install)
- [Future features](#future-features)
- [Contribution](#contribution)
- [License](#license)

<small><i><a href='http://ecotrust-canada.github.io/markdown-toc/'>Table of contents generated with markdown-toc</a></i></small>

### Sources

* [javbus](https://www.javbus.com/)
* [javlibrary](http://www.javlibrary.com/)
* [avgle](https://avgle.io)

### Install

**From Source**

1. Cloned it from github or download package as zip.
2. Unzip code to your webserver.
3. `composer install -vvv`

**From Docker**

[![Docker Stars](https://img.shields.io/docker/stars/hldh214/gas.svg)](https://hub.docker.com/r/hldh214/gas/)
[![Docker Pulls](https://img.shields.io/docker/pulls/hldh214/gas.svg)](https://hub.docker.com/r/hldh214/gas/)
[![Docker Automated buil](https://img.shields.io/docker/automated/hldh214/gas.svg)](https://hub.docker.com/r/hldh214/gas/)

``` sh
$ docker run -d -p 8964:8964 hldh214/gas
# query by code
$ curl -s "localhost:8964/web/query?code=ABS-130" | jq
# rand
$ curl -s "localhost:8964/web/rand" | jq
```

### Future features

* more readable code.
* more source.

### Contribution

Feel free to contribute.

* Found a bug? Try to find it in issue tracker https://github.com/hldh214/gas/issues ... If this bug is missing - you can add an issue about it.
* Can/want/like develop? Create pull request and I will check it in nearest time! 


### License

Gas is open-source software licensed under the MIT License. See the LICENSE file for more information.
