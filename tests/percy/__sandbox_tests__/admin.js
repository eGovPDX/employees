const puppeteer = require('puppeteer')
var fs = require('fs');

const SITE_NAME = process.env.SITE_NAME;
const HOME_PAGE = (SITE_NAME) ? `https://sandbox.employees.portland.gov` : 'https://employees.lndo.site';
const ARTIFACTS_FOLDER = (SITE_NAME) ? `/home/circleci/artifacts/` : `./`;

var BROWSER_OPTION = {
  ignoreHTTPSErrors: true,
  args: ["--no-sandbox", "--ignore-certificate-errors"],
  defaultViewport: null,
};

describe('SuperAdmin user test', () => {
  var browser, page, login_url;
  beforeAll(async () => {
    browser = await puppeteer.launch(BROWSER_OPTION)
    page = await browser.newPage()
    await page.setDefaultTimeout(30000)

    if (process.env.CIRCLECI) {
      // On CI, the CI script will call terminus to retrieve login URL
      login_url = process.env.SUPERADMIN_LOGIN;
      await page.goto(login_url);
    }
    else {
      var drush_uli_result = fs.readFileSync("superAdmin_uli.log").toString();
      login_url = drush_uli_result.replace('http://default', 'https://employees.lndo.site');
      // Log in once for all tests to save time
      await page.goto(login_url);
    }
  })

  afterAll(async () => {
    await browser.close()
  })

  it(
    'superAdmin creates sandbox alert',
    async function () {
      try {
        let text_content = '', selector='';

        // Create the sandbox alert
        await page.goto(`${HOME_PAGE}/node/add/alert`, { waitUntil: 'networkidle2' });
        text_content = await page.evaluate(() => document.querySelector('.page-title').textContent);
        expect(text_content).toEqual(expect.stringContaining('Create Alert'));
        await page.type('#edit-title-0-value', 'Sandbox Site'); 
        await page.select('#edit-field-severity', '30');    // Information
        await page.evaluate(() => {
          ck5 = Drupal.CKEditor5Instances.keys().next().value;
          editor = Drupal.CKEditor5Instances.get(ck5);
          editor.setData('This site is a Monday morning snapshot copy of employees.portland.gov and provides a safe environment for editor training classes, self learning, or experimentation, etc. Changes made here will not affect the live site and cannot be imported into the live site.');
        });
        // Set publish date and time
        await page.evaluate(() => {
          var date = new Date();
          date.setMinutes(date.getMinutes() - 480);   // Convert UTC to PST
          
          var day = date.getDate(),
              month = date.getMonth() + 1,
              year = date.getFullYear(),
              hour = date.getHours(),
              min  = date.getMinutes();
          
          month = (month < 10 ? "0" : "") + month;
          day = (day < 10 ? "0" : "") + day;
          hour = (hour < 10 ? "0" : "") + hour;
          min = (min < 10 ? "0" : "") + min;
          
          var today = year + "-" + month + "-" + day,
              displayTime = hour + ":" + min;

          document.querySelector('#edit-publish-on-0-value-date').value = today;
          document.querySelector('#edit-publish-on-0-value-time').value = displayTime;
        });
        await page.select("#edit-moderation-state-0-state", "published");
        
        // Save alert
        selector = '#edit-submit';
        await page.evaluate((selector) => document.querySelector(selector).click(), selector);
        await page.waitForNavigation();

        text_content = await page.evaluate(() => document.querySelector('h1.page-title').textContent);
        expect(text_content).toEqual(expect.stringContaining('Sandbox Site'));
      } catch (e) {
        // Capture the screenshot when test fails and re-throw the exception
        await page.screenshot({
          path: `${ARTIFACTS_FOLDER}sandbox-error.jpg`,
          type: "jpeg",
          fullPage: true
        });
        throw e;
      }
    }
  );
});
