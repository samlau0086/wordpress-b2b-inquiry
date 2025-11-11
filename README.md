# B2B Inquiry Plugin

The B2B Inquiry plugin provides a reusable modal form that lets visitors send quote or inquiry requests from any WordPress page. Each submission is stored as a custom post type so your sales team can review, update, and follow up on leads directly inside the WordPress dashboard.

## Features

- Adds a shortcode-rendered button that opens an inquiry modal on any page or post.
- Automatically pre-fills the modal with the current page title, URL, and logged-in user email (when available).
- Captures email, phone, and message content, then stores each submission as a **B2B Inquiry** post with full CRUD support.
- Supports configurable message templates with `{title}` and `{url}` placeholders.
- Sends notifications through unlimited email recipients and webhook endpoints.

## Requirements

- WordPress 5.8 or newer.
- PHP 7.4 or newer.

## Installation

1. Copy the plugin folder to `wp-content/plugins/` on your WordPress site.
2. Log in to the WordPress admin panel and navigate to **Plugins → Installed Plugins**.
3. Activate **B2B Inquiry**.

The plugin registers the `b2b_inquiry` custom post type and adds a **Settings** submenu under **B2B Inquiries** for notification management.

## Embedding the Inquiry Button

Use the `[b2b_inquiry_button]` shortcode anywhere shortcodes are supported (posts, pages, widgets, page builders, etc.). The shortcode renders an inquiry button that opens the modal with the form.

### Customizing the Button Label

You can override the button text with the `button_text` attribute:

```text
[b2b_inquiry_button button_text="Request a Quote"]
```

## Front-End Experience

- Clicking the inquiry button reveals a modal overlay that covers the entire viewport.
- The modal displays the current page title and URL so the visitor knows which product or service they are inquiring about.
- Hidden fields ensure the page title and URL are stored with each submission even though they are not editable by the visitor.
- The form validates that **Email** and **Message** are provided before submitting.
- Success and error messages appear beneath the form. After a successful submission, the form resets while keeping the confirmation visible.

## Managing Inquiries in the Dashboard

1. Go to **B2B Inquiries** in the WordPress admin menu to view submissions.
2. Each entry displays the associated email, phone number, and page information in the list table.
3. Click an inquiry to open the detail view. The meta box shows email, phone, page title, and page URL—these values are editable if updates are needed.
4. You can add notes or follow-up details in the main content editor, change the post status, or trash inquiries as necessary.

## Notification Settings

Open **B2B Inquiries → Settings** to configure alerts triggered on every submission.

### Email Notifications

- Use the **Notification Emails** list to add one or more recipient addresses.
- Click **Add Email** to append a new field, enter the address, and save changes.
- All listed addresses receive an email containing the inquiry details.

### Webhook Notifications

- Use the **Webhook URLs** list to define one or more endpoints that should receive JSON payloads when a visitor submits the form.
- Click **Add Webhook** to insert a new URL field.
- Each endpoint receives a JSON object with `email`, `phone`, `message`, `page_url`, and `page_title` properties.

### Message Template

- Configure the default message in the **Default Message Template** field.
- Use `{title}` and `{url}` placeholders to inject the current page data automatically.
- Visitors can further customize the message before sending.

## Localization

Text strings use WordPress internationalization functions. Place translated files in `languages/` if you create locale-specific `.mo`/`.po` files.

## Uninstallation

Deactivating the plugin keeps previously stored inquiries in the database. Remove the plugin files manually if you no longer need the functionality.

## Troubleshooting

- Ensure that a WordPress cron or external service handles outgoing emails if notifications are not received.
- Check the **Tools → Site Health** screen for REST API or loopback issues that could affect webhook delivery.
- Use your browser's developer tools to monitor AJAX requests if the modal form does not submit correctly.

