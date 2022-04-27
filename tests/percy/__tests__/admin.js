const percySnapshot = require("@percy/puppeteer");
const puppeteer = require("puppeteer");

const SITE_NAME = process.env.SITE_NAME;
const PROJECT_NAME = process.env.CIRCLE_PROJECT_REPONAME
const HOME_PAGE = SITE_NAME
    ? `https://${SITE_NAME}-${PROJECT_NAME}.pantheonsite.io`
    : "https://employees.lndo.site";
const ARTIFACTS_FOLDER = (SITE_NAME) ? `/home/circleci/artifacts/` : `./`;

let BROWSER_OPTION = {
    ignoreHTTPSErrors: true,
    args: ["--no-sandbox"],
    defaultViewport: null,
};

// Go to home page
describe('Visual Regression Testing', () => {
    let browser, page;
    beforeAll(async () => {
        browser = await puppeteer.launch(BROWSER_OPTION);
        page = await browser.newPage();

        if (process.env.CIRCLECI) {
            // On CI, the CI script will call terminus to retrieve login URL
            login_url = process.env.SUPERADMIN_LOGIN;
            await page.goto(login_url);
        }
        else {
            var drush_uli_result = fs.readFileSync("superAdmin_uli.log").toString();
            login_url = drush_uli_result.replace('http://default', 'https://portlandor.lndo.site');
            // Log in once for all tests to save time
            await page.goto(login_url);
        }
    })

    afterAll(async () => {
        await browser.close();
    })

    it('Home Page', async function () {
        try {
            await page.goto(HOME_PAGE)
            await percySnapshot(page, "Authenticated - Home Page")
        } catch (e) {
            // Capture the screenshot when test fails and re-throw the exception
            await page.screenshot({
                path: `${ARTIFACTS_FOLDER}config-import-error.jpg`,
                type: "jpeg",
                fullPage: true
            });
            throw e;
        }
    })
})
// Take snapshot

// Go to City Web Editors Group page
// Take snapshot

//Go to news
// Take snapshot
//Go to events
// Take snapshot
//Go to technology catalogue
// Take snapshot
//Go to contacts
// Take snapshot
//Go to group list view
// Take snapshot