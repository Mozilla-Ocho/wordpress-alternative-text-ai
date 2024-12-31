# Contributing to Smart Alt Text

Thank you for your interest in contributing to Smart Alt Text! We welcome contributions from everyone who wants to help make image accessibility better on WordPress sites.

## Code of Conduct

By participating in this project, you agree to abide by the WordPress [Code of Conduct](https://make.wordpress.org/handbook/community-code-of-conduct/).

## Getting Started

### Finding Issues to Work On

* Check our [GitHub Issues](https://github.com/yourusername/smart-alt-text/issues) page
* Look for issues tagged with `good-first-issue`, `help-wanted`, or `bug`
* New issues are typically triaged weekly

### Reporting Bugs

If you've found a bug, please create an issue with:

* A clear, descriptive title
* Your WordPress version, PHP version, and browser details
* Steps to reproduce the bug
* Expected behavior vs actual behavior
* Any relevant error messages or screenshots
* Console logs if applicable

Example:
```
Title: Alt text not generating for PNG images

Environment:
- WordPress 6.4.2
- PHP 8.1
- Chrome 120.0.6099.109
- Smart Alt Text 1.0.0

Steps to Reproduce:
1. Upload a PNG image
2. Click "Analyze"
3. Nothing happens

Expected: Alt text should be generated
Actual: No response, no error message
```

### Development Setup

1. Fork the repository
2. Clone your fork:
```bash
git clone https://github.com/YOUR-USERNAME/smart-alt-text.git
cd smart-alt-text
```

3. Create a branch for your changes:
```bash
git checkout -b fix/issue-description
```

4. Make your changes and test thoroughly

### Pull Request Process

1. Update the README.md if needed (e.g., for new features)
2. Follow the existing code style and conventions
3. Add/update PHPUnit tests if applicable
4. Ensure all tests pass
5. Update your branch with the latest main:
```bash
git fetch origin
git rebase origin/main
```

6. Push your changes and create a pull request

### Pull Request Template

When creating a PR, please include:

```markdown
## Description
Brief description of the changes

## Related Issue
Fixes #(issue number)

## Testing Instructions
1. Step one
2. Step two
3. etc.

## Screenshots (if applicable)

## Types of changes
- [ ] Bug fix
- [ ] New feature
- [ ] Breaking change
- [ ] Documentation update
```

### Git Commit Messages

Follow these guidelines for commit messages:

1. Use the present tense ("Add feature" not "Added feature")
2. Use the imperative mood ("Move cursor to..." not "Moves cursor to...")
3. Limit the first line to 72 characters or less
4. Reference issues and pull requests liberally after the first line

Example:
```
Add image type validation before analysis

- Add file type checking before sending to X.AI API
- Support only JPG and PNG formats
- Show user-friendly error for unsupported types

Fixes #42
```

### Code Review Process

1. All code changes require review
2. Reviewers will look for:
   - Correct functionality
   - Code style and standards
   - Test coverage
   - Documentation
3. Address review comments with new commits
4. Once approved, squash commits if needed
5. Maintainers will merge the PR

### Development Guidelines

1. Follow WordPress [Coding Standards](https://developer.wordpress.org/coding-standards/)
2. Add comments for complex logic
3. Write/update tests for new features
4. Keep accessibility in mind
5. Ensure security best practices

## Questions or Need Help?

* Create a [GitHub Discussion](https://github.com/yourusername/smart-alt-text/discussions)
* Check existing issues and discussions first
* Provide as much context as possible

Thank you for contributing to Smart Alt Text! 