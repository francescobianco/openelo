


push:
	@git add .
	@git commit -m "update"
	@git push origin main

start:
	@docker compose up -d --build
	@echo "Visit http://localhost:8080 for the application."