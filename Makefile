


push:
	@git add .
	@git commit -m "update"
	@git push origin main

start:
	@docker compose up -d --build
	@echo "Visit http://localhost:8080 for the application."



.PHONY: ftp-deploy

# Usage:
#   make ftp-deploy file=prod.lftp
ftp-deploy:
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
