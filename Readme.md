# Usage

## Fetch a manga
* get the `copy as cURL` URL from the first volume of a serie
* enter a temporary directory
* launch the script to crawl
`php ~/git/sushicrawl/sushi.php`
* package all volumes
`for foo in `find . -type d -mindepth 1`; do zip -r $foo.cbz $foo; rm -rf $foo; done`
