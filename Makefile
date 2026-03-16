
.PHONY: ftp-deploy

push:
	@git config credential.helper 'cache --timeout=3600'
	@git add .
	@git commit -m "update" || true
	@git push origin main

start:
	@docker compose up -d --build
	@docker compose exec openelo bash -c "rm -fr /var/www/html/data/emails/* || true"
	@echo "Visit http://localhost:8080 for the application."

migrate:
	@docker compose exec openelo php -f migrate.php

# Push a demo seed file to a running instance.
# Usage:
#   make demo-seed file=demo/simple.json                        # local (localhost:8080)
#   make demo-seed file=demo/simple.json url=https://demo.openelo.org
# Requires APP_SECRET to be set in the environment or .env file.
demo-seed:
	@sh -c '\
	FILE="$(file)"; \
	URL="$${url:-http://localhost:8080}"; \
	SECRET="$${APP_SECRET}"; \
	if [ -z "$$FILE" ]; then echo "❌ file not set. Usage: make demo-seed file=demo/simple.json"; exit 1; fi; \
	if [ ! -f "$$FILE" ]; then echo "❌ file not found: $$FILE"; exit 1; fi; \
	if [ -z "$$SECRET" ]; then echo "❌ APP_SECRET not set in environment"; exit 1; fi; \
	echo "🚀 Pushing $$FILE to $$URL ..."; \
	curl -sf -X POST \
	  -H "Content-Type: application/json" \
	  -H "X-App-Secret: $$SECRET" \
	  --data-binary "@$$FILE" \
	  "$$URL/?page=api&action=demo_seed" | cat; \
	echo ""; \
	'

# Usage:
#   make ftp-deploy file=prod.lftp
ftp-deploy: push
	@sh -c '\
	SCRIPT="$(file)"; \
	if [ -z "$$SCRIPT" ]; then \
	  echo "❌ file not set. Usage: make ftp-deploy file=<script.lftp>"; exit 1; \
	fi; \
	if [ ! -f "$$SCRIPT" ]; then \
	  echo "❌ script file not found: $$SCRIPT"; exit 1; \
	fi; \
	SCRIPT=$$(realpath "$$SCRIPT"); \
	echo "✅ Using script: $$SCRIPT"; \
	DEPLOY_SRC=$$(pwd)/.deploy; \
	rm -rf "$$DEPLOY_SRC"; mkdir -p "$$DEPLOY_SRC"; \
	echo "📦 Exporting repository to $$DEPLOY_SRC ..."; \
	git archive --format=tar HEAD | tar -x -C "$$DEPLOY_SRC"; \
	cd "$$DEPLOY_SRC"; \
	echo "🚀 Running lftp with script $$SCRIPT"; \
	lftp -f "$$SCRIPT"; \
	'
