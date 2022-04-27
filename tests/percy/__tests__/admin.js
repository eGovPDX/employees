const percySnapshot = require("@percy/puppeteer");
const puppeteer = require("puppeteer");

const SITE_NAME = process.env.SITE_NAME;
const PROJECT_NAME = process.env.CIRCLE_PROJECT_REPONAME
const HOME_PAGE = SITE_NAME
    ? `https://${SITE_NAME}-${PROJECT_NAME}.pantheonsite.io`
    : "https://employees.lndo.site";

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
    })

    afterAll(async () => {
        await browser.close();
    })

    it('Home Page', async function () {
        page.goto(HOME_PAGE)
        await percySnapshot(page, "Authenticated - Home Page")
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