// Run with `npm test`. The script pins TZ=Asia/Manila (UTC+8, no DST) so the conversions are predictable.
import { test } from "node:test";
import assert from "node:assert/strict";
import { formatDateTime, localInputToUtc, utcToLocalInput } from "./datetime.ts";

test("runs in UTC+8", () => {
  assert.equal(new Date("2030-01-15T00:00:00Z").getTimezoneOffset(), -480);
});

test("utcToLocalInput converts UTC to local datetime-local format", () => {
  assert.equal(utcToLocalInput("2030-01-15T02:00:00Z"), "2030-01-15T10:00");
});

test("utcToLocalInput rolls over to the next local day", () => {
  assert.equal(utcToLocalInput("2030-01-15T20:30:00Z"), "2030-01-16T04:30");
});

test("utcToLocalInput returns an empty string for invalid input", () => {
  assert.equal(utcToLocalInput("not-a-date"), "");
});

test("localInputToUtc converts local datetime-local input to UTC ISO", () => {
  assert.equal(localInputToUtc("2030-01-15T10:00"), "2030-01-15T02:00:00.000Z");
});

test("localInputToUtc and utcToLocalInput round-trip", () => {
  const local = "2030-06-01T23:45";
  assert.equal(utcToLocalInput(localInputToUtc(local)), local);
});

test("localInputToUtc leaves empty and invalid values for the API to reject", () => {
  assert.equal(localInputToUtc(""), "");
  assert.equal(localInputToUtc("nonsense"), "nonsense");
});

test("formatDateTime shows local time", () => {
  assert.equal(formatDateTime("2030-01-15T02:00:00Z", "en-GB"), "15 Jan 2030, 10:00");
});
