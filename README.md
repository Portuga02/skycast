# 🌤️ SkyCast PRO

![Status do Projeto](https://img.shields.io/badge/status-ativo-success.svg)
![Laravel](https://img.shields.io/badge/laravel-%23FF2D20.svg?style=flat&logo=laravel&logoColor=white)
![Vue.js](https://img.shields.io/badge/vuejs-%2335495e.svg?style=flat&logo=vuedotjs&logoColor=%234FC08D)
![TailwindCSS](https://img.shields.io/badge/tailwindcss-%2338B2AC.svg?style=flat&logo=tailwind-css&logoColor=white)
![Licença](https://img.shields.io/badge/license-MIT-blue.svg)

> **SkyCast PRO** é uma aplicação de previsão do tempo de alta precisão, projetada para resolver desafios complexos de desambiguação geográfica. Construída com um backend robusto em **Laravel** e um frontend reativo em **Vue.js 3**, ela implementa padrões avançados de manipulação de eventos e arquitetura para garantir a precisão dos dados em localidades limítrofes.

---

## 📸 Preview do Projeto

<p align="center">
  <img src="./screenshot.png" alt="Painel SkyCast PRO" width="100%">
</p>

---

## 🚀 Destaques de Engenharia e Decisões Arquiteturais

Este projeto demonstra soluções para problemas críticos de engenharia de software encontrados em aplicações baseadas em geolocalização.

### 1. Estratégia de Desambiguação Geográfica
**O Problema:** Buscar por uma cidade como "Prata" frequentemente retorna falsos positivos (ex: "Nova Prata" no RS) porque APIs padrão priorizam população ou relevância em vez da correspondência exata.
**A Solução:**
- Implementação de uma **Construção de Consulta Composta** no Frontend.
- O sistema constrói um payload estrito: `Nome da Cidade - Código do Estado, Código do País` (ex: `Prata - PB, BR`).
- Isso força a API de Geocoding a filtrar estritamente por região, garantindo 100% de precisão mesmo para cidades pequenas que compartilham nomes com grandes centros.

### 2. Tratamento de "Race Conditions" no Frontend
**O Problema:** Em componentes de Autocomplete reativos, o evento `blur` (perda de foco do input) geralmente dispara antes do evento `click` na lista suspensa. Isso faz com que a lista feche antes que a seleção do usuário seja registrada.
**A Solução:**
- Utilização do modificador de evento `@mousedown.prevent` em vez de `@click`.
- Isso intercepta a prioridade do loop de eventos do navegador, garantindo que a lógica de seleção seja executada e o estado seja atualizado **antes** que o elemento de input perca o foco.

### 3. Lógica de Exibição vs. Lógica Técnica (View Override)
**O Problema:** APIs de clima muitas vezes retornam o nome da estação meteorológica mais próxima (ex: "Ouro Velho") em vez da cidade pequena solicitada ("Prata"), confundindo o usuário.
**A Solução:**
- Implementação do padrão **View Override** (Sobrescrita de Visualização).
- A aplicação armazena o nome selecionado pelo usuário em uma variável de estado separada (`display_name`) para persistir o contexto da UI, enquanto utiliza as coordenadas técnicas em segundo plano para a recuperação dos dados.

### 4. Roteamento Robusto e Codificação de URL
**O Problema:** Passar strings complexas contendo hifens, vírgulas e espaços (ex: `Recife - PE, BR`) quebra os parâmetros de rota RESTful padrão.
**A Solução:**
- Configuração de Rotas Laravel com **Restrições Regex** (`->where('city', '.*')`) para aceitar caracteres arbitrários.
- Implementação de pipelines de sanitização estrita com `encodeURIComponent` (Frontend) e `urldecode` (Backend).

---

## 🛠️ Stack Tecnológica

### Backend (API)
- **Framework:** Laravel 10
- **Arquitetura:** MVC + Service Pattern
- **Cliente HTTP:** Guzzle (via Laravel Http Facade)
- **Cache:** File Driver (TTL de 15 minutos para respostas da API)

### Frontend (Cliente)
- **Framework:** Vue.js 3 (Composition API)
- **Estilização:** Tailwind CSS (Utility-first)
- **Gerenciamento de Estado:** Reactive Refs & LocalStorage (Persistência de Histórico)
- **Cliente HTTP:** Axios

### Serviços Externos
- **Dados Climáticos:** OpenWeatherMap (Current Weather Data 2.5)
- **Geocoding:** OpenWeatherMap (Geocoding API 1.0)

---

## ⚡ Instalação e Configuração

### Pré-requisitos
- PHP 8.1+
- Composer
- Node.js & NPM

### 1. Clonar o repositório
```bash
git clone [https://github.com/seu-usuario/skycast-pro.git](https://github.com/seu-usuario/skycast-pro.git)
cd skycast-pro

2. Configuração do BackendBash# Instalar dependências PHP
composer install

# Configuração de ambiente
cp .env.example .env
php artisan key:generate
3. Configuração da API KeyAdicione sua chave da OpenWeather API ao arquivo .env:Fragmento do códigoOPENWEATHER_API_KEY=sua_chave_aqui
4. Configuração do FrontendBash# Instalar dependências Node
npm install

# Compilar assets (Hot Reload)
npm run dev
5. Executar o ServidorEm um novo terminal:Bashphp artisan serve
Acesse a aplicação em: http://localhost:8000🔌 Endpoints da APIMétodoEndpointDescriçãoGET/api/cidades/busca/{query}Retorna lista de cidades para Autocomplete (Geocoding)GET/api/clima/{city}Retorna dados climáticos detalhados para uma string de localização

Gerenciamento de Cache
Para garantir que as alterações de roteamento sejam aplicadas corretamente durante o desenvolvimento:

Bash

php artisan route:clear
php artisan cache:clear
php artisan config:clear
👤 Autor
[Sávio Gomes da Silva ] Engenheiro de Software Fullstack | Especialista em Laravel & Vue.js

Projeto desenvolvido para fins de demonstração de arquitetura de software.