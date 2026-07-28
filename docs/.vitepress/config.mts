import { defineConfig } from 'vitepress'

export default defineConfig({
  title: "Laravel Async API",
  description: "Automatically generate AsyncAPI documentation from Laravel events.",
  themeConfig: {
    search: {
      provider: 'local'
    },
    nav: [
      { text: 'Home', link: '/' },
      { text: 'Documentation', link: '/introduction' },
      { text: 'Packagist', link: 'https://packagist.org/packages/victormgomes/async-api' }
    ],
    sidebar: [
      {
        text: 'Getting Started',
        items: [
          { text: 'Introduction', link: '/introduction' },
          { text: 'Installation', link: '/installation-and-quick-start' },
        ]
      },
      {
        text: 'Usage',
        items: [
          { text: 'The AsyncApi Attribute', link: '/usage' },
          { text: 'Configuration', link: '/configuration' },
        ]
      },
      {
        text: 'Resources',
        items: [
          { text: 'Changelog', link: 'https://github.com/victormgomes/async-api/releases' },
          { text: 'Upgrading', link: '/UPGRADING' },
        ]
      }
    ],
    socialLinks: [
      { icon: 'github', link: 'https://github.com/victormgomes/async-api' }
    ]
  }
})
