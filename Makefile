# vim: set tabstop=8 softtabstop=8 noexpandtab:
.PHONY: help
help: ## 📖 Displays this list of targets with descriptions
	@grep -E '^[a-zA-Z0-9_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}'

.PHONY: static-code-analysis
static-code-analysis: vendor ## 🔍 Runs a static code analysis with phpstan/phpstan
	vendor/bin/phpstan analyse --configuration=phpstan-default.neon.dist --memory-limit=-1

.PHONY: static-code-analysis-baseline
static-code-analysis-baseline: check-symfony vendor ## 📊 Generates a baseline for static code analysis with phpstan/phpstan
	vendor/bin/phpstan analyse --configuration=phpstan-default.neon.dist --generate-baseline=phpstan-default-baseline.neon --memory-limit=-1

.PHONY: tests
tests: vendor ## 🧪 Run tests
	vendor/bin/phpunit tests

.PHONY: vendor
vendor: composer.json composer.lock ## 📦 Installs composer dependencies
	composer install

.PHONY: cs
cs: ## ✨ Update Coding Standards
	vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.dist.php --diff --verbose

.PHONY: clean
clean: ## 🧹 Clean build artifacts
	rm -rf vendor composer.lock db/multiflexi.sqlite

.PHONY: migration
migration: ## 🚀 Run database migrations
	cd db ; ../vendor/bin/phinx migrate -c ../phinx-adapter.php ; cd ..

.PHONY: sysmigration
sysmigration: ## 🔧 Run database migrations using system phinx
	cd db ; /usr/bin/phinx migrate -c /usr/lib/multiflexi/phinx-adapter.php ; cd ..

.PHONY: seed
seed: ## 🌱 Run database seeds
	cd db ; ../vendor/bin/phinx seed:run -c ../phinx-adapter.php ; cd ..

.PHONY: autoload
autoload: ## 🔄 Run composer autoload
	composer update

demodata: ## 🎭 Load demo data
	cd db ; ../vendor/bin/phinx seed:run -c ../phinx-adapter.php ; cd ..

.PHONY: newmigration

newmigration: ## ➕ Prepare new Database Migration (interactive if no name given)
	@read -p "Enter CamelCase migration name : " migname ; cd db ; ../vendor/bin/phinx create $$migname -c ../phinx-adapter.php ; cd ..

newmigration-%: ## ➕ Prepare new Database Migration with given name
	cd db ; ../vendor/bin/phinx create $* -c ../phinx-adapter.php ; cd ..

newseed: ## 🌿 Create new seed
	read -p "Enter CamelCase seed name : " migname ; cd db ; ../vendor/bin/phinx seed:create $$migname -c ./phinx-adapter.php ; cd ..

reset-sqlite: ## 🔄 Reset SQLite database
	sudo rm -f db/multiflexi.sqlite
	echo > db/multiflexi.sqlite
	chmod 666 db/multiflexi.sqlite
	chmod ugo+rwX db
	make migration

reset-mysql: ## Force reset MySQL database
	echo 'drop database multiflexi; create database multiflexi;' | sudo mysql
	make migration

demo: dbreset migration demodata ## 🎯 Setup demo environment

postinst: ## 📥 Test postinst script
	DEBCONF_DEBUG=developer /usr/share/debconf/frontend /var/lib/dpkg/info/multiflexi.postinst configure $(nextversion)

redeb: ## 🔨 Rebuild and reinstall package
	 sudo apt -y purge multiflexi; rm ../multiflexi_*_all.deb ; debuild -us -uc ; sudo gdebi  -n ../multiflexi_*_all.deb ; sudo apache2ctl restart

debs: ## 📦 Build debian packages
	debuild -i -us -uc -b

reset: ## ⚠️ Reset to origin
	git fetch origin
	git reset --hard origin/$(git rev-parse --abbrev-ref HEAD)

