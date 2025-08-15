# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is an SSO (Single Sign-On) proof of concept project. The repository is currently empty and ready for initial setup.

## Development Setup

Since this is a new project, you'll need to initialize it based on the requirements. Common SSO implementations typically involve:

- Web application framework (React, Vue, Angular, etc.)
- Backend API (Node.js, Python, Java, etc.)
- Authentication providers (OAuth2, SAML, OpenID Connect)
- Database for user sessions and configuration

## Project Structure

This repository is currently empty. When implementing the SSO proof of concept, consider organizing the code with:

- Frontend application in a `client/` or `frontend/` directory
- Backend services in a `server/` or `backend/` directory
- Shared configuration and documentation in the root
- Docker configuration for containerized deployment
- Environment-specific configuration files

## Common Commands

Since the project is not yet initialized, standard commands will depend on the chosen technology stack:

For Node.js projects:
- `npm install` - Install dependencies
- `npm run dev` - Start development server
- `npm run build` - Build for production
- `npm test` - Run tests
- `npm run lint` - Run linting

For Python projects:
- `pip install -r requirements.txt` - Install dependencies
- `python -m pytest` - Run tests
- `python app.py` - Start application

## Security Considerations

As this is an SSO implementation:
- Never commit secrets, API keys, or certificates to the repository
- Use environment variables for sensitive configuration
- Implement proper token validation and expiration
- Follow OAuth2/OpenID Connect security best practices
- Ensure proper CORS and CSRF protection