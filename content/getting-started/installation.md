---
title: Installation
description: "Install the CodeQuill CLI via npm, configure environment variables, and set up authentication for local and CI environments."
order: 3
---

# Installation

The CodeQuill CLI is a Node.js application distributed via npm. It runs on macOS, Linux, and Windows.

## Requirements

- **Node.js** 18.0 or later
- **Git** installed and available in your PATH
- A GitHub remote configured for your repository (the CLI auto-detects the repository name from the git origin)

## Install via npm

```bash
npm install -g codequill
```

Verify the installation:

```bash
codequill --version
```

## Install a Specific Version

To pin to a specific version (useful for CI environments):

```bash
npm install -g codequill@0.8.1
```

## Environment Variables

The CLI behavior can be customized through environment variables:

| Variable | Description | Default |
|---|---|---|
| `CODEQUILL_API_BASE_URL` | Backend API base URL | `https://api.codequill.xyz` |
| `CODEQUILL_BASE_URL` | Web application URL (used for display links) | `https://app.codequill.xyz` |
| `CODEQUILL_CONFIG_DIR` | Override the directory for authentication tokens | `~/.config/codequill` |
| `CODEQUILL_TOKEN` | Bearer token for CI/non-interactive use (bypasses login) | -- |
| `CODEQUILL_GITHUB_ID` | GitHub repository numeric ID (used with `CODEQUILL_TOKEN`) | -- |

### CI Authentication

In CI environments (GitHub Actions, GitLab CI, etc.), you can bypass the interactive login flow by setting `CODEQUILL_TOKEN`:

```bash
export CODEQUILL_TOKEN="your-repo-scoped-token"
export CODEQUILL_GITHUB_ID="${{ github.repository_id }}"
```

When `CODEQUILL_TOKEN` is set, the CLI uses it directly as a bearer token instead of reading from stored credentials. The `CODEQUILL_GITHUB_ID` is sent as an `X-GITHUB-ID` header to identify the repository context.

Repo-scoped tokens can be generated from the web application's repository settings.

## Uninstall

```bash
npm uninstall -g codequill
```

To also remove stored credentials:

```bash
rm -rf ~/.config/codequill
```

To remove local repository data:

```bash
rm -rf .codequill
```
