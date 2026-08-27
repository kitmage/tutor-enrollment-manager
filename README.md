# WooCommerce Tutor LMS Training Entitlements

A production-oriented WordPress plugin that turns each paid WooCommerce line item into a distinct, shareable batch of Tutor LMS course enrollment seats.

## Installation and dependencies

Copy this directory to `wp-content/plugins/woocommerce-tutor-training-entitlements`, activate it, then visit **Settings → Permalinks** if routes were cached. WordPress, WooCommerce, and Tutor LMS are required. WooCommerce Subscriptions is optional and detected without a hard dependency. Data is retained on deactivation.

The plugin creates prefix-aware `training_entitlement_batches`, `training_entitlement_redemptions`, and `training_entitlement_audit` tables. Schema upgrades are versioned and run only when necessary.

## Product configuration

In a product's **General** data panel, enable Training Entitlements, choose a published Tutor course, and enter positive entitlement and window values. Quantity multiplies seats. Variable products may override course, seats, and window on each variation; blank overrides inherit the parent. Subscription products use the same fields.

## Provisioning, subscriptions, refunds, and expiration

Paid/processing/completed orders are provisioned idempotently. The unique order-item key prevents duplicate batches. A renewal order has new line-item IDs and therefore receives an independent allocation; unused seats never roll over. Each expiration is calculated with the WordPress timezone. Full refunds revoke funded batches without deleting enrollments or history.

## Redemption

Customers see **My Account → Training Enrollments**, including status, remaining seats, an encrypted-at-rest shareable link, and the immutable trainee-name roster. Anonymous recipients are sent through the normal WordPress/WooCommerce account system with a return URL. Authenticated recipients explicitly submit a nonce-protected redemption.

Capacity is reserved using a conditional atomic SQL update. Tutor enrollment is attempted only after reservation, verified through `EnrollmentModel::is_enrolled()`, and the seat is released on failure. `do_enroll()` is invoked through a reflected, version guard; when Tutor returns an enrollment ID that is not active, supported `update_enrollments()` is used to promote it to `completed`, followed by verification. Tutor-specific behavior is isolated in `Enrollment_Service`. Validate this adapter against the exact Tutor LMS release in staging when upgrading Tutor LMS.

## Administration and security

WooCommerce → Training Entitlements supports search/filtering, roster inspection, revocation, valid reactivation, token regeneration, expiration updates, and total updates (never below completed redemptions). Every change is audited. Admin actions require `manage_woocommerce` and nonces. SQL is prepared, output escaped, input sanitized, public pages disclose only course and expiration, and only token HMACs are stored in the batch table. Recoverable token material is AES-256-GCM encrypted in private order metadata.

## Hooks

* `wcte_batch_created( $batch_id, $order_id, $order_item_id )`
* `wcte_redemption_completed( $batch_id, $user_id, $course_id, $order_id )`

## Known limitations

* Actual WooCommerce/Tutor integration tests require a complete WordPress test installation and the exact commercial plugin versions.
* Account registration availability follows WooCommerce/WordPress site settings.
* The MVP has one shared token per batch. The batch/redemption separation permits future invitation-specific records without changing entitlement accounting.
