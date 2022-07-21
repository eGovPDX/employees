exports.removeEnvironmentIndicator = async function (page) {
    await page.evaluate(
        () => {
          document.querySelector("#toolbar-item-environment-indicator").innerText = ""
          
        })
}
