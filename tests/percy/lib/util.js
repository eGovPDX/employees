exports.removeEnvironmentIndicator = async function (page) {
  await page.locator("a#toolbar-item-environment-indicator").evaluate(el => el.innerText = "");
}
