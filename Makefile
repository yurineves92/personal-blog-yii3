DC := docker compose

.PHONY: help up down build logs shell yii composer migrate seed reset

help: ## Lista os comandos
	@grep -E '^[a-zA-Z_-]+:.*?## ' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-10s\033[0m %s\n", $$1, $$2}'

up: ## Sobe os contêineres (build se necessário)
	$(DC) up -d --build

down: ## Para os contêineres
	$(DC) down

build: ## Reconstrói a imagem PHP
	$(DC) build php

logs: ## Acompanha os logs
	$(DC) logs -f

shell: ## Shell no contêiner PHP
	$(DC) exec php sh

yii: ## Executa um comando do Yii. Ex.: make yii c="migrate:history"
	$(DC) exec php ./yii $(c)

composer: ## Executa o Composer. Ex.: make composer c="require foo/bar"
	$(DC) exec php composer $(c)

migrate: ## Aplica as migrations pendentes
	$(DC) exec php ./yii migrate:up --no-interaction

seed: ## Popula o banco com dados de exemplo (se vazio)
	$(DC) exec php ./yii app:seed

reset: ## Apaga o banco (volume) e recria tudo do zero
	$(DC) down -v
	$(DC) up -d --build
