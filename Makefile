
test:
	vendor/bin/phpcbf --report=full --report-file=./report.txt -p ./src
	vendor/bin/phpstan analyse -c phpstan.neon -l max src
