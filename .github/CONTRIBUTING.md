# Contributing Guidelines

✨ Before we get started, thank you for taking the time to contribute! ✨

This is a guideline for contributing to the project, its documentation, and other repositories. We welcome your feedback, proposed changes, and updates to these guidelines. We will always welcome thoughtful issues and consider pull requests.

But, please take a moment to review this document **before submitting a pull request**.

## Before contributing

### Transl isn’t FOSS

While Transl's source code is open source, publicly available, and welcomes contributions, it is proprietary. Everything in this repository, including any community-contributed code, is the property of Transl. For that reason there are a few limitations on how you can use the code:

- You cannot alter anything related to licensing, updating, version or edition checking, purchasing, first party notifications or banners, or anything else that attempts to circumvent paying for features that are designated as paid features. We want to stay in business so we can better support _you_ and the community.
- You can’t publicly maintain a long-term fork of the repository.

### How to Get Support

If you're looking for official developer support (and you have an active license/subscription), send us an email at the address that can be found on [Transl.me](https://transl.me). We will always do our best to reply in a timely manner. **Github issues are intended for reporting bugs.**

## Contributing

### Pull requests

**Please ask first before starting work on any significant new features.**

It's never a fun experience to have your pull request declined after investing a lot of time and effort into a new feature. To avoid this from happening, we request that contributors [share their idea with us](https://github.com/transl-me/laravel-transl/discussions/new?category=ideas) in our discussion forum to first discuss any significant new features.

### Coding standards

Our code formatting rules are defined in [pint.json](https://github.com/transl-me/laravel-transl/blob/main/pint.json). You can check your code against these standards by running:

```sh
composer format
```

To automatically fix any style violations in your code, you can run:

```sh
composer fix
```

### Static analysis

You can analyse the codebase with phpstan using the following command:

```sh
composer analyse
```

or the alias:

```sh
composer lint
```

### Running tests

You can run the test suite using the following commands:

```sh
composer test
```

Please ensure that the tests are passing when submitting a pull request. If you're adding new features, please include tests.
