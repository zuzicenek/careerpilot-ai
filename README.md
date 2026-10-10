# CareerPilot AI

An AI-assisted career advisor that helps users understand job requirements, compare skills, and identify practical next steps.

## Overview

CareerPilot AI is a student portfolio project built with PHP and HTML/CSS. It combines rule-based skill matching with AI-generated career guidance using the Google Gemini API.

The project explores how AI can support job seekers without treating an automated match score as a guarantee of employment.

## Features

- **Job offer analysis:** Summarizes the role and its likely responsibilities.
- **Skill matching:** Compares skills mentioned in a job description with skills provided by the user.
- **Indicative match score:** Displays the percentage of recognized requirements that match the user's listed skills.
- **Career recommendations:** Suggests practical steps to improve job readiness.
- **Fallback results:** Provides rule-based analysis when the AI service is unavailable.

## Technology Stack

- PHP
- HTML5
- CSS3
- Google Gemini API
- Git and GitHub
- XAMPP for local development

## Getting Started

### Requirements

- PHP with the cURL extension enabled
- XAMPP or another PHP-compatible local web server
- A Google Gemini API key

### Installation

1. Clone or download this repository.
2. Place the project folder in your web server's document root. With XAMPP on Windows, this is usually `C:\xampp\htdocs\`.
3. Create a file named `config.local.php` in the project root.
4. Add your Gemini API key using the configuration example below.
5. Start Apache in XAMPP.
6. Open `http://localhost/careerpilot-ai/` in your browser.

### API Configuration

Create `config.local.php`:

```php
<?php

return [
    'gemini_api_key' => 'YOUR_GEMINI_API_KEY',
];
```

Replace the placeholder with your own API key.

**Security:** Never publish your real API key. The `config.local.php` file is excluded from Git using `.gitignore`. API availability and usage limits depend on Google's current service terms and quotas.

## How It Works

1. The user enters a job description, their skills, and the target role.
2. PHP compares recognized skills using a rule-based dictionary.
3. The application sends the information to Gemini for career analysis.
4. The results page displays the match score, recognized skill gaps, and recommendations.
5. If AI analysis is unavailable, the rule-based results remain available.

## Limitations

- Skill matching is limited to the terms currently included in the application's dictionary.
- The match score is indicative and is not the probability of being hired.
- AI-generated recommendations may be inaccurate or incomplete.
- AI analysis requires a working internet connection and an available Gemini API service.
- This project currently runs locally; it does not include a public hosted demo.

## Future Improvements

- Expand skill recognition and support synonyms.
- Improve error handling and automated tests.
- Add screenshots and accessibility improvements.
- Explore deployment to a public hosting environment.

## Project Status

Student portfolio project — functional prototype.

## Author

Developed as a student project exploring PHP web development, API integration, rule-based analysis, and AI-assisted career guidance.