---
title: Posting Announcements
slug: posting-announcements
order: 10
---

## Overview

Announcements let authorized members broadcast one-way updates to your {{ entity_label }}—ideal for urgent notices, policy reminders, or scheduled news that should not live in a discussion thread.

## Who can post

Members with the **Post Announcements** permission (or {{ entity_label }} owners) can create, edit, and delete announcements. All other members can read published announcements targeted to their role.

## Steps

1. Open **Communications → Announcements** from the main navigation.
2. Enter a **title** and **message**.
3. Optionally set a **publish date** to schedule the announcement for later.
4. Optionally enable **Send email** to deliver the announcement by email when it publishes.
5. Optionally restrict visibility with **Target roles** so only members in selected roles see the announcement.
6. Save the announcement.

## Reading and tracking

- Unread announcements show a badge on **Communications** and **Announcements** in the navigation.
- Open an announcement and use **Mark as read** to clear it from your unread count.
- Authors can view read statistics showing how many eligible members have opened each announcement.

## Scheduled email

When **Send email** is enabled, emails are sent automatically when the announcement publishes. The host application runs `announcements:send-scheduled` on a schedule to deliver messages for scheduled posts.

## Best practices

- Use announcements for broadcasts; use [Discussions](/help/communications/starting-a-discussion) for two-way conversation.
- Target roles when a notice applies only to council, treasurers, or another subset of members.
- Keep titles short so members can scan the list quickly.

## See also

- [Communications overview](/help/communications/overview)
- [Starting a discussion](/help/communications/starting-a-discussion)
