---
title: Subscription Plans
order: 3
---

# Subscription Plans

CodeQuill subscriptions determine the quotas available to a workspace for claims, snapshots, attestations, preservations, CI/CD integrations, and collaborators. Billing is managed through Stripe.

## Plan Tiers

Four plans are available. All quotas are per workspace, per billing period.

### Starter (Free)

The Starter plan provides baseline access for individual developers evaluating CodeQuill.

| Resource | Quota |
|---|---|
| Public claims | 5 |
| Private claims | 0 |
| Snapshots | 2 |
| Attestations | 0 |
| Preservations | 0 |
| CI/CD repositories | 0 |
| Collaborators | 0 |

The Starter plan has no cost and no time limit. It is intended for initial exploration -- claiming a few public repositories and recording a small number of snapshots to understand the system before committing to a paid tier.

### Builder ($9/month or $97/year)

The Builder plan is suited for active individual developers or small teams beginning to integrate CodeQuill into their workflow.

| Resource | Quota |
|---|---|
| Public claims | 50 |
| Private claims | 5 |
| Snapshots | 30 |
| Attestations | 0 |
| Preservations | 0 |
| CI/CD repositories | 3 |
| Collaborators | 0 |

The annual price of $97 reflects a discount over the monthly rate. The Builder plan introduces CI/CD integration, enabling automated snapshot recording for up to three repositories.

### Professional ($29/month or $313/year)

The Professional plan supports teams that require attestations, collaborator access, and higher snapshot volumes.

| Resource | Quota |
|---|---|
| Public claims | 200 |
| Private claims | 25 |
| Snapshots | 200 |
| Attestations | 50 |
| Preservations | 0 |
| CI/CD repositories | 10 |
| Collaborators | 5 |

At this tier, attestations become available, enabling build artifact lineage tracking against published releases. Collaborator seats allow team members to participate under shared workspace governance.

### Organization ($99/month or $1,188/year)

The Organization plan is designed for companies, open-source foundations, and DAOs that operate at scale.

| Resource | Quota |
|---|---|
| Public claims | 1,000 |
| Private claims | 250 |
| Snapshots | 2,000 |
| Attestations | 500 |
| Preservations | 100 |
| CI/CD repositories | 50 |
| Collaborators | 20 |

The Organization plan provides the full feature set at volume. Preservations (encrypted source archives) are available at this tier, along with substantial attestation and collaborator capacity.

[Screenshot: Subscription plans page showing all four tiers with feature comparison]

## Billing Management

Subscription billing is handled entirely through Stripe. From the workspace settings, you can:

- **Upgrade or downgrade** your plan. Changes take effect at the start of the next billing cycle. Downgrades that would exceed the new plan's quotas display a warning; existing records are not deleted, but new operations beyond the lower quota are blocked.
- **Switch between monthly and annual billing** at renewal time.
- **Update payment methods** via the Stripe customer portal.
- **View invoices and payment history** through the Stripe portal link in settings.
- **Cancel** a paid subscription. The workspace reverts to the Starter plan at the end of the current billing period.

CodeQuill does not store payment card details. All payment processing is delegated to Stripe.

## Quota Display

Current quota usage is visible in the workspace settings under the billing section. Each resource type displays:

- The current count of resources used in the billing period.
- The total quota allowed by the active plan.
- A visual indicator when usage approaches or reaches the limit.

When a quota is exhausted, the corresponding operation is blocked in both the web application and the CLI. The error message identifies which quota was exceeded and which plan tier would accommodate the required capacity.

## Partner Benefits

Holders of the **Book of Ethereum (BOOE)** token receive bonus quotas on top of their subscription plan. The benefit applies when a workspace's authority wallet holds at least **4,000 BOOE** tokens.

Partner benefits are additive: they increase the quota ceiling for the active plan without changing the plan itself. The specific bonus amounts are displayed in the billing section when the connected authority wallet qualifies.

BOOE token balance is checked against the authority wallet at the time of each on-chain operation. If the balance drops below the 4,000 BOOE threshold, bonus quotas are no longer applied to subsequent operations. Existing records created under the bonus quota are unaffected.
