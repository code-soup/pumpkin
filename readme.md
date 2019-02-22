## WordPress Starter Theme ##

##### Install instructrions #####
- Add ACF_PRO_KEY to your .env file to enable download of ACF PRO from private repository

1. Clone repository
`~ git clone git@github.com:code-soup/pumpkin.git .`

2. Start fresh with every new theme by deleting git repository and creating new one
`~ rm -rf .git`
`~ git init`
`~ git add .`
`~ git commit -am 'init'`

3. Install NPM dependencies
`~ yarn`

4. Install Composer dependencies
`~ composer install`