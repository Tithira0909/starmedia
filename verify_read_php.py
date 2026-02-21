from playwright.sync_api import sync_playwright
import time
import os

def run():
    with sync_playwright() as p:
        browser = p.chromium.launch()
        page = browser.new_page()

        # Load read.php with the test file
        url = "http://localhost:8000/read.php?file=test.pdf"
        try:
            response = page.goto(url)
            print(f"Loaded {url}, status: {response.status}")

            if response.status != 200:
                print("FAIL: read.php returned non-200 status")
                print(page.content())
                return

            # Check title
            title = page.title()
            print(f"Page Title: {title}")
            if "Reading: test.pdf" in title:
                print("PASS: Title is correct")
            else:
                print("FAIL: Title mismatch")

            # Check viewer div
            if page.locator("#viewer").count() > 0:
                print("PASS: Viewer div found")
            else:
                print("FAIL: Viewer div not found")

            # Check loading text
            if page.locator("#loading").is_visible():
                print("PASS: Loading indicator is initially visible")

            # Since it's a dummy empty PDF, PDF.js might fail or error out, but we check if the script logic ran.
            # We can check if pdfjsLib is defined.
            is_defined = page.evaluate("typeof pdfjsLib !== 'undefined'")
            if is_defined:
                print("PASS: pdfjsLib is loaded")
            else:
                print("FAIL: pdfjsLib is NOT loaded")

        except Exception as e:
            print(f"Error: {e}")

        browser.close()

if __name__ == "__main__":
    run()
