# Firefly III Data Importer with Basiq Integration

[![Packagist][packagist-shield]][packagist-url]
[![License][license-shield]][license-url]
[![Stargazers][stars-shield]][stars-url]
[![Donate][donate-shield]][donate-url]

<!-- PROJECT LOGO -->
<br />
<p align="center">
  <a href="https://firefly-iii.org/">
    <img src="https://raw.githubusercontent.com/firefly-iii/firefly-iii/develop/.github/assets/img/logo-small.png" alt="Firefly III" width="120" height="178">
  </a>
</p>
  <h1 align="center">Firefly III Data Importer</h1>

  <p align="center">
    Import your transactions into Firefly III
    <br />
    <a href="https://docs.firefly-iii.org/"><strong>Explore the documentation</strong></a>
    <br />
    <br />
    <a href="https://github.com/firefly-iii/firefly-iii/issues">Report a bug</a>
    ·
    <a href="https://github.com/firefly-iii/firefly-iii/issues">Request a feature</a>
    ·
    <a href="https://github.com/firefly-iii/firefly-iii/discussions">Ask questions</a>
  </p>


## About the data importer

"Firefly III" is a (self-hosted) manager for your personal finances. It can help you keep track of your expenses and income, so you can spend less and save more. The **Firefly III Data Importer** is built to help you import transactions into Firefly III. It is separated from Firefly III for security and maintenance reasons.

This version includes integration with **Basiq**, allowing you to import data directly from Australian banks.

## Basiq Integration Setup

To use the Basiq integration, you will need a Basiq API Key.

### Environment Variables

You can configure the Basiq integration using the following environment variable:

*   `BASIQ_API_KEY`: Your Basiq API Key. If provided here, you won't need to enter it in the web interface.

### Persistence

The Basiq integration requires persistent storage to remember your Basiq User ID and connected banks, so you don't have to re-authenticate every time you run an import.

This is handled via a SQLite database located at `database/database.sqlite`. When running via Docker, ensure you mount a volume to persist this file.

### Docker Usage

You can build and run the importer using the provided `Dockerfile`.

**Build:**

```bash
docker build -t firefly-iii-data-importer .
```

**Run:**

```bash
docker run -d \
  -p 8080:80 \
  -e FIREFLY_III_URL=http://your-firefly-instance \
  -e FIREFLY_III_ACCESS_TOKEN=your-access-token \
  -e BASIQ_API_KEY=your-basiq-api-key \
  -e DB_CONNECTION=sqlite \
  -e DB_DATABASE=/var/www/html/database/database.sqlite \
  -v $(pwd)/database:/var/www/html/database \
  firefly-iii-data-importer
```

**Note:** Ensure the `database` directory exists on your host and is writable by the container user (typically `www-data`, uid 33). You may need to run `chown -R 33:33 database` on your host.

## About the original project

The data importer does not connect to your bank directly. Instead, it uses [third party data providers](https://docs.firefly-iii.org/how-to/data-importer/import/third-party-providers/) to help you import data into Firefly III. Some of these providers are free of charge, others charge money.

If you do not want to rely on third parties to import your data, you can import data using the following file formats:

- CSV
- CAMT.052
- CAMT.053

Other formats are on my to do list :-).

You can run the data importer once, for a bulk import. You can also run it regularly to keep up with new transactions.

Eager to get started? Go to [the documentation](https://docs.firefly-iii.org/)!

## Features

* Import from many banks using third party data providers
* **New: Import from Australian banks via Basiq**
* Import over the command line for easy automation
* Import over an API for easy automation
* Use rules and data mapping for transaction clarity

Many more features are listed in the [documentation](https://docs.firefly-iii.org/).

## Who's it for?

This application is for people who want to track their finances, keep an eye on their money **without having to upload their financial records to the cloud**. You're a bit tech-savvy, you like open source software, and you don't mind tinkering with (self-hosted) servers.

## Getting Started

Many more features are listed in the [documentation](https://docs.firefly-iii.org/).

## Contributing

You can contact me at [james@firefly-iii.org](mailto:james@firefly-iii.org), you may open an issue in the [main repository](https://github.com/firefly-iii/firefly-iii) or contact me through [gitter](https://gitter.im/firefly-iii/firefly-iii) and [Mastodon](https://fosstodon.org/@ff3).

Of course, there are some [contributing guidelines](https://github.com/firefly-iii/data-importer/blob/main/.github/contributing.md) and a [code of conduct](https://github.com/firefly-iii/data-importer/blob/main/.github/code_of_conduct.md), which I invite you to check out.

I can always use your help [squashing bugs](https://docs.firefly-iii.org/explanation/support/#contributing-code), thinking about [new features](https://docs.firefly-iii.org/explanation/support/#contributing-code) or [translating Firefly III](https://docs.firefly-iii.org/how-to/firefly-iii/development/translations/) into other languages.

There is also a [security policy](https://github.com/firefly-iii/data-importer/security/policy).

<!-- SPONSOR TEXT -->

## Support the development of Firefly III

Firefly III is a side gig. With your sponsorship or support, I can spend more time on Firefly III. So, if you like Firefly III, and if it helps you save lots of money, why not send me a dime for every dollar saved! 🥳

OK, that was a joke. But for real, when you feel Firefly III made your life better, please consider contributing as a sponsor. Please check out my [Patreon](https://www.patreon.com/jc5) and [GitHub Sponsors](https://github.com/sponsors/JC5) page for more information. You can also [buy me a ☕️ coffee at ko-fi.com](https://ko-fi.com/Q5Q5R4SH1) or send something my way using [Liberapay](https://liberapay.com/JC5). Thank you for your consideration.

### Sponsorships

Firefly III is sponsored by LamdaTest. Their support allows me to test Firefly III more easily and introduce even fewer bugs with every release.

<p style="font-size:21px; color:black;">Browser testing via
<a href="https://www.lambdatest.com/?utm_source=fireflyiii&utm_medium=sponsor" target="_blank">
<img src="https://www.lambdatest.com/blue-logo.png" style="vertical-align: middle;" width="250" height="45" />
</a>
</p>

<!-- END OF SPONSOR TEXT -->

## License

This work [is licensed](https://github.com/firefly-iii/data-importer/blob/main/LICENSE) under the [GNU Affero General Public License v3](https://www.gnu.org/licenses/agpl-3.0.html).

<!-- HELP TEXT -->

## Do you need help, or do you want to get in touch?

Do you want to contact me? You can email me at [james@firefly-iii.org](mailto:james@firefly-iii.org) or get in touch through one of the following support channels:

- [GitHub Discussions](https://github.com/firefly-iii/firefly-iii/discussions/) for questions and support
- [Gitter.im](https://gitter.im/firefly-iii/firefly-iii) for a good chat and a quick answer
- [GitHub Issues](https://github.com/firefly-iii/firefly-iii/issues) for bugs and issues. Issues are collected centrally, in the [Firefly III repository](https://github.com/firefly-iii/firefly-iii).
- <a rel="me" href="https://fosstodon.org/@ff3">Mastodon</a> for news and updates

<!-- END OF HELP TEXT -->

## Acknowledgements

The Firefly III logo is made by the excellent Cherie Woo.

[packagist-shield]: https://img.shields.io/packagist/v/firefly-iii/data-importer.svg?style=flat-square
[packagist-url]: https://packagist.org/packages/firefly-iii/data-importer
[license-shield]: https://img.shields.io/github/license/firefly-iii/data-importer.svg?style=flat-square
[license-url]: https://www.gnu.org/licenses/agpl-3.0.html
[stars-shield]: https://img.shields.io/github/stars/firefly-iii/data-importer.svg?style=flat-square
[stars-url]: https://github.com/firefly-iii/data-importer/stargazers
[donate-shield]: https://img.shields.io/badge/donate-%24%20%E2%82%AC-brightgreen?style=flat-square
[donate-url]: #support-the-development-of-firefly-iii
[hack-shield]: https://cdn.huntr.dev/huntr_security_badge_mono.svg
[hack-url]: https://huntr.dev/bounties/disclose
