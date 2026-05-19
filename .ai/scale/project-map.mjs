#!/usr/bin/env node
var __defProp = Object.defineProperty;
var __export = (target, all) => {
  for (var name in all)
    __defProp(target, name, { get: all[name], enumerable: true });
};

// src/projectMap/cli.ts
import path10 from "path";
import { fileURLToPath } from "node:url";

// src/projectMap/constants.ts
import path from "path";
function getPaths(projectRoot) {
  const PROJECT_ROOT = projectRoot ?? process.cwd();
  const AI_DIR = path.join(PROJECT_ROOT, ".ai");
  const SCALE_DIR = path.join(AI_DIR, "scale");
  const STATE_DIR = path.join(SCALE_DIR, "state");
  const POSTINGS_DIR = path.join(STATE_DIR, "postings");
  const SYNOPSES_DIR = path.join(STATE_DIR, "synopses");
  const SYNOPSES_DIRS_DIR = path.join(SYNOPSES_DIR, "dirs");
  const SYNOPSES_FILES_DIR = path.join(SYNOPSES_DIR, "files");
  const QUERIES_DIR = path.join(STATE_DIR, "queries");
  return {
    PROJECT_ROOT,
    AI_DIR,
    SCALE_DIR,
    STATE_DIR,
    POSTINGS_DIR,
    SYNOPSES_DIR,
    SYNOPSES_DIRS_DIR,
    SYNOPSES_FILES_DIR,
    QUERIES_DIR
  };
}
var PROJECT_MAP_VERSION = "1.1.0";
var DEFAULT_BUILD_CONCURRENCY_LIMIT = 8;
var DEFAULT_BUILD_WRITE_CONCURRENCY_LIMIT = 32;
var DEFAULT_MAX_INDEXABLE_LINE_LENGTH = 2e3;

// src/projectMap/build/collect.ts
import fs3 from "node:fs/promises";
import path7 from "path";
import os from "node:os";
import { performance as performance3 } from "node:perf_hooks";

// src/constants.ts
var IGNORED_DIRECTORY_NAMES = /* @__PURE__ */ new Set([
  ".git",
  ".github",
  ".hg",
  ".svn",
  ".idea",
  ".vs",
  ".vscode",
  ".next",
  ".nuxt",
  ".obsidian",
  ".cache",
  ".turbo",
  "node_modules",
  "vendor",
  "dist",
  "build",
  "coverage",
  "tmp",
  "temp",
  "__pycache__"
]);
var IGNORED_RELATIVE_DIRECTORIES = /* @__PURE__ */ new Set([
  ".ai/out",
  ".ai/scale",
  ".ai/scale/state"
]);
var BINARY_EXTENSIONS = /* @__PURE__ */ new Set([
  ".7z",
  ".a",
  ".ai",
  ".avi",
  ".bin",
  ".bmp",
  ".class",
  ".dll",
  ".dmg",
  ".doc",
  ".docx",
  ".eot",
  ".exe",
  ".gif",
  ".gz",
  ".ico",
  ".jar",
  ".jpeg",
  ".jpg",
  ".lib",
  ".lockb",
  ".mov",
  ".mp3",
  ".mp4",
  ".o",
  ".obj",
  ".otf",
  ".pdf",
  ".png",
  ".psd",
  ".so",
  ".tar",
  ".tif",
  ".tiff",
  ".ttf",
  ".wav",
  ".webm",
  ".webp",
  ".woff",
  ".woff2",
  ".xls",
  ".xlsx",
  ".zip"
]);
var GENERATED_FILE_PATTERNS = [
  /\.min\.[^.]+$/i,
  /\.map$/i,
  /package-lock\.json$/i,
  /pnpm-lock\.ya?ml$/i,
  /yarn\.lock$/i,
  /composer\.lock$/i,
  /diff\.diff$/i,
  /Cargo\.lock$/i,
  /poetry\.lock$/i
];
var DOC_EXTENSIONS = /* @__PURE__ */ new Set([".md", ".markdown", ".mdx", ".rst", ".txt", ".adoc"]);
var CONFIG_EXTENSIONS = /* @__PURE__ */ new Set([".json", ".jsonc", ".yaml", ".yml", ".toml", ".ini", ".cfg", ".conf", ".env", ".properties", ".xml"]);
var DATA_EXTENSIONS = /* @__PURE__ */ new Set([".csv", ".tsv", ".sql"]);
var SCRIPT_EXTENSIONS = /* @__PURE__ */ new Set([".sh", ".bash", ".ps1", ".bat", ".cmd"]);
var SOURCE_EXTENSIONS = /* @__PURE__ */ new Set([
  ".c",
  ".cc",
  ".cpp",
  ".cs",
  ".css",
  ".go",
  ".h",
  ".hpp",
  ".html",
  ".java",
  ".js",
  ".jsx",
  ".mjs",
  ".php",
  ".pl",
  ".py",
  ".rb",
  ".rs",
  ".scss",
  ".sass",
  ".ts",
  ".tsx",
  ".vue"
]);
var TEST_HINTS = ["test", "tests", "spec", "__tests__", ".spec.", ".test."];
var DOC_HINTS = ["docs", "doc", "readme", "guide", "manual", "notes", "design", "lore", "campaign", "adventure"];
var CONFIG_HINTS = ["config", "configs", "settings"];

// src/ignore.ts
function isUnderIgnoredRelativeDirectory(relativePath) {
  const normalized = relativePath === "." ? "." : relativePath.replace(/^\.\//, "");
  for (const ignoredDirectory of IGNORED_RELATIVE_DIRECTORIES) {
    if (normalized === ignoredDirectory || normalized.startsWith(`${ignoredDirectory}/`)) {
      return true;
    }
  }
  return false;
}
function shouldIgnoreDirectory(relativeDirectoryPath, directoryName) {
  if (IGNORED_DIRECTORY_NAMES.has(directoryName)) {
    return true;
  }
  return isUnderIgnoredRelativeDirectory(relativeDirectoryPath);
}

// src/utils.ts
import path2 from "path";

// src/text/stopwords.ts
var STOPWORDS = /* @__PURE__ */ new Set([
  "a",
  "an",
  "and",
  "are",
  "as",
  "at",
  "be",
  "but",
  "by",
  "for",
  "from",
  "if",
  "in",
  "into",
  "is",
  "it",
  "its",
  "of",
  "on",
  "or",
  "that",
  "the",
  "their",
  "then",
  "there",
  "these",
  "this",
  "to",
  "was",
  "were",
  "will",
  "with",
  "you",
  "your",
  "we",
  "our",
  "can",
  "could",
  "should",
  "would",
  "may",
  "might",
  "not",
  "than",
  "when",
  "where",
  "what",
  "which",
  "who",
  "why",
  "how",
  "do",
  "does",
  "did",
  "done",
  "using",
  "use",
  "used",
  "via",
  "also",
  "such",
  "only",
  "very",
  "more",
  "most",
  "much",
  "many"
]);

// src/text/identifiers.ts
var DEFAULT_TOP_IDENTIFIERS = 12;
function extractIdentifiers(text, limit = DEFAULT_TOP_IDENTIFIERS) {
  const matches = String(text ?? "").match(/[A-Za-z_][A-Za-z0-9_:-]{2,}/g) ?? [];
  const counts = /* @__PURE__ */ new Map();
  for (const match of matches) {
    const looksAllLower = /^[a-z0-9_:-]+$/.test(match);
    const looksCommonWord = STOPWORDS.has(match.toLowerCase());
    if (looksAllLower && looksCommonWord) {
      continue;
    }
    counts.set(match, (counts.get(match) ?? 0) + 1);
  }
  return [...counts.entries()].sort((left, right) => {
    const countDelta = right[1] - left[1];
    if (countDelta !== 0) {
      return countDelta;
    }
    return left[0].localeCompare(right[0]);
  }).slice(0, limit).map(([identifier, count]) => ({ identifier, count }));
}

// src/utils.ts
function hasText(value) {
  return typeof value === "string" && value.trim().length > 0;
}
var TOKEN_RE = /[A-Za-z0-9][A-Za-z0-9._:/-]*/g;
var SEP_RE = /[._:/-]+/;
var TRIM_PUNCT_RE = /^[-_.:\/]+|[-_.:\/]+$/g;
var NORMALIZE_TERM_CACHE_LIMIT = 1e5;
var normalizeTermCache = /* @__PURE__ */ new Map();
var STOPWORDS2 = /* @__PURE__ */ new Set(["a", "an", "the", "and", "or", "of", "in", "to"]);
function truncate(value, maxLength = 240) {
  if (!hasText(value)) {
    return "";
  }
  return value.length <= maxLength ? value : `${value.slice(0, maxLength - 3)}...`;
}
function normalizeWhitespace(value) {
  return String(value ?? "").replace(/\s+/g, " ").trim();
}
function toPosixPath(inputPath) {
  return inputPath.replace(/\\/g, "/");
}
function toRelativeProjectPath(absolutePath, projectRoot = process.cwd()) {
  const relative = path2.relative(projectRoot, absolutePath);
  return toPosixPath(relative || ".");
}
function safeSlug(value, fallback = "query") {
  const cleaned = String(value ?? "").trim().toLowerCase().replace(/[^a-z0-9._-]+/g, "-").replace(/^-+|-+$/g, "").replace(/-{2,}/g, "-");
  return cleaned || fallback;
}
function splitCamelCase(token) {
  return token.replace(/([a-z0-9])([A-Z])/g, "$1 $2").replace(/([A-Z]+)([A-Z][a-z])/g, "$1 $2").split(/\s+/).filter(Boolean);
}
function normalizeTerm(term) {
  const key = String(term ?? "");
  const cached = normalizeTermCache.get(key);
  if (cached !== void 0) return cached;
  const out = key.trim().toLowerCase().replace(TRIM_PUNCT_RE, "");
  if (normalizeTermCache.size >= NORMALIZE_TERM_CACHE_LIMIT) {
    normalizeTermCache.clear();
  }
  normalizeTermCache.set(key, out);
  return out;
}
function isUsefulTerm(term) {
  if (!term || term.length < 2) {
    return false;
  }
  if (STOPWORDS2.has(term)) {
    return false;
  }
  if (/^\d+$/.test(term) && term.length < 4) {
    return false;
  }
  return true;
}
function countTokenizedTerms(text) {
  const counts = /* @__PURE__ */ new Map();
  forEachTokenizedTerm(text, (term) => {
    counts.set(term, (counts.get(term) ?? 0) + 1);
  });
  return counts;
}
function topTermsFromCounts(termCounts, limit = 15) {
  return [...termCounts.entries()].sort((l, r) => {
    const d = r[1] - l[1];
    if (d !== 0) {
      return d;
    }
    return l[0].localeCompare(r[0]);
  }).slice(0, limit).map(([term, count]) => ({ term, count }));
}
function buildPreviewFromLines(lines, maxLines = 3, maxLength = 240) {
  const previewLines = [];
  for (const line of lines) {
    const trimmed = line.trim();
    if (!trimmed) {
      continue;
    }
    previewLines.push(trimmed);
    if (previewLines.length >= maxLines) {
      break;
    }
  }
  return truncate(previewLines.join(" | "), maxLength);
}
function hasLineLongerThan(text, maxLength) {
  if (!text) return false;
  let run = 0;
  for (let i = 0; i < text.length; i++) {
    const code = text.charCodeAt(i);
    if (code === 10 || code === 13) {
      if (run > maxLength) return true;
      run = 0;
      continue;
    }
    run += 1;
    if (run > maxLength) return true;
  }
  if (run > maxLength) return true;
  return false;
}
function extractQuotedStrings(text, limit = 8) {
  const matches = [];
  const pattern = /["'`]([^"'`\n]{3,120})["'`]/g;
  let m;
  while ((m = pattern.exec(text)) !== null) {
    matches.push(m[1]);
    if (matches.length >= limit) {
      break;
    }
  }
  return matches;
}
function bucketForTerm(term) {
  const first = term[0] ?? "";
  if (/[a-z]/.test(first)) {
    return first;
  }
  if (/[0-9]/.test(first)) {
    return "num";
  }
  return "other";
}
function tokenizeText(text) {
  const output = [];
  forEachTokenizedTerm(text, (term) => output.push(term));
  return output;
}
function forEachTokenizedTerm(text, emit) {
  const rawTokens = String(text ?? "").match(TOKEN_RE) ?? [];
  for (const rawToken of rawTokens) {
    const base = normalizeTerm(rawToken);
    if (isUsefulTerm(base)) {
      emit(base);
    }
    for (const separatorPart of rawToken.split(SEP_RE)) {
      if (!separatorPart) continue;
      const normalizedPart = normalizeTerm(separatorPart);
      if (isUsefulTerm(normalizedPart)) {
        emit(normalizedPart);
      }
      const camelParts = splitCamelCase(separatorPart);
      for (const camelPart of camelParts) {
        const normalizedCamelPart = normalizeTerm(camelPart);
        if (isUsefulTerm(normalizedCamelPart)) {
          emit(normalizedCamelPart);
        }
      }
    }
  }
}

// src/projectMap/io.ts
import fs from "node:fs/promises";
import path3 from "path";
async function readJson(filePath) {
  const text = await fs.readFile(filePath, "utf8");
  return JSON.parse(text);
}
async function writeJson(filePath, obj) {
  const dir = path3.dirname(filePath);
  await fs.mkdir(dir, { recursive: true });
  await fs.writeFile(filePath, JSON.stringify(obj, null, 2), "utf8");
}
async function readJsonLines(filePath) {
  const text = await fs.readFile(filePath, "utf8");
  if (!text) {
    return [];
  }
  return text.split(/\r?\n/).filter(Boolean).map((line) => JSON.parse(line));
}
async function writeJsonLines(filePath, objects) {
  const dir = path3.dirname(filePath);
  await fs.mkdir(dir, { recursive: true });
  const stream = objects.map((o) => JSON.stringify(o)).join("\n") + "\n";
  await fs.writeFile(filePath, stream, "utf8");
}
async function ensureScaleDirectory(aiDir) {
  await fs.mkdir(aiDir, { recursive: true });
}
async function ensureStateDirectories(paths) {
  await fs.mkdir(paths.STATE_DIR, { recursive: true });
  await fs.mkdir(paths.POSTINGS_DIR, { recursive: true });
  await fs.mkdir(paths.SYNOPSES_DIRS_DIR, { recursive: true });
  await fs.mkdir(paths.SYNOPSES_FILES_DIR, { recursive: true });
  await fs.mkdir(paths.QUERIES_DIR, { recursive: true });
}
async function removeDirectoryIfPresent(pathToRemove) {
  try {
    await fs.rm(pathToRemove, { recursive: true, force: true });
  } catch (err) {
  }
}

// src/readBinarySample.ts
import fs2 from "node:fs/promises";
async function readBinarySample(filePath, maxBytes = 4096) {
  const handle = await fs2.open(filePath, "r");
  try {
    const buffer = Buffer.alloc(maxBytes);
    const { bytesRead } = await handle.read(buffer, 0, maxBytes, 0);
    return buffer.subarray(0, bytesRead);
  } finally {
    await handle.close();
  }
}

// src/isTextFile.ts
async function isTextFile(filePath, extension) {
  const ext = String(extension ?? "").toLowerCase();
  if (BINARY_EXTENSIONS.has(ext)) {
    return false;
  }
  const sample = await readBinarySample(filePath, 4096);
  if (sample.length === 0) {
    return true;
  }
  let nullByteCount = 0;
  let suspiciousControlCount = 0;
  for (const byte of sample) {
    if (byte === 0) {
      nullByteCount += 1;
      continue;
    }
    const isTab = byte === 9;
    const isLineFeed = byte === 10;
    const isCarriageReturn = byte === 13;
    const isPrintableAscii = byte >= 32 && byte <= 126;
    if (!isTab && !isLineFeed && !isCarriageReturn && !isPrintableAscii) {
      suspiciousControlCount += 1;
    }
  }
  if (nullByteCount > 0) {
    return false;
  }
  const suspiciousRatio = suspiciousControlCount / sample.length;
  return suspiciousRatio < 0.25;
}

// src/looksGenerated.ts
function looksGenerated(relativeFilePath) {
  return GENERATED_FILE_PATTERNS.some((pattern) => pattern.test(relativeFilePath));
}

// src/classifyFile.ts
function classifyFile(relativeFilePath, extension, isTextFile2) {
  const lowerPath = relativeFilePath.toLowerCase();
  const ext = String(extension ?? "").toLowerCase();
  if (!isTextFile2) {
    return "binary";
  }
  if (looksGenerated(relativeFilePath)) {
    return "generated";
  }
  if (TEST_HINTS.some((hint) => lowerPath.includes(hint))) {
    return "test";
  }
  if (DOC_EXTENSIONS.has(ext) || DOC_HINTS.some((hint) => lowerPath.includes(hint))) {
    return "doc";
  }
  if (CONFIG_EXTENSIONS.has(ext) || CONFIG_HINTS.some((hint) => lowerPath.includes(hint))) {
    return "config";
  }
  if (DATA_EXTENSIONS.has(ext)) {
    return "data";
  }
  if (SCRIPT_EXTENSIONS.has(ext)) {
    return "script";
  }
  if (SOURCE_EXTENSIONS.has(ext)) {
    return "source";
  }
  if (ext && !isTextFile2) {
    return "asset";
  }
  return isTextFile2 ? "unknown" : "asset";
}

// src/boundary.ts
var DECLARATION_PATTERNS = [
  /^\s*(?:export\s+)?(?:async\s+)?function\s+[A-Za-z_]/,
  /^\s*(?:public\s+|private\s+|protected\s+)?function\s+[A-Za-z_]/i,
  /^\s*class\s+[A-Za-z_]/,
  /^\s*(?:interface|enum|namespace|module|trait)\s+[A-Za-z_]/i,
  /^\s*(?:def|fn)\s+[A-Za-z_]/,
  /^\s*(?:describe|it|test)\s*\(/,
  /^\s*[A-Za-z_][A-Za-z0-9_]*\s*:\s*$/
];
var MARKDOWN_HEADING_PATTERN = /^\s{0,3}#{1,6}\s+(.+)$/;
var UNDERLINE_HEADING_PATTERN = /^\s*(?:={3,}|-{3,})\s*$/;
var INI_SECTION_PATTERN = /^\s*\[[^\]]+\]\s*$/;
var DELIMITER_PATTERN = /^\s*[-=*#_]{4,}\s*$/;
var FENCE_PATTERN = /^\s*```/;
var HTML_HEADING_PATTERN = /^\s*<h[1-6][^>]*>(.*?)<\/h[1-6]>\s*$/i;
function inferBoundaryKind(lines, startIndex) {
  const line = lines[startIndex] ?? "";
  if (MARKDOWN_HEADING_PATTERN.test(line) || HTML_HEADING_PATTERN.test(line)) {
    return "heading";
  }
  if (INI_SECTION_PATTERN.test(line)) {
    return "section";
  }
  if (DELIMITER_PATTERN.test(line)) {
    return "delimiter";
  }
  if (FENCE_PATTERN.test(line)) {
    return "fence";
  }
  for (const pattern of DECLARATION_PATTERNS) {
    if (pattern.test(line)) {
      return "declaration";
    }
  }
  return "section";
}
function inferBoundaryTitle(lines, startIndex) {
  const line = lines[startIndex] ?? "";
  const markdownMatch = line.match(MARKDOWN_HEADING_PATTERN);
  if (markdownMatch) {
    return normalizeWhitespace(markdownMatch[1]);
  }
  const htmlMatch = line.match(HTML_HEADING_PATTERN);
  if (htmlMatch) {
    return normalizeWhitespace(htmlMatch[1]);
  }
  if (INI_SECTION_PATTERN.test(line)) {
    return normalizeWhitespace(line.replace(/^\s*\[|\]\s*$/g, ""));
  }
  if (DELIMITER_PATTERN.test(line)) {
    const previousLine = lines[startIndex - 1] ?? "";
    if (hasText(previousLine)) {
      return normalizeWhitespace(previousLine);
    }
  }
  for (const pattern of DECLARATION_PATTERNS) {
    if (pattern.test(line)) {
      return normalizeWhitespace(line);
    }
  }
  return "";
}
function detectBoundaries(lines) {
  const boundaries = /* @__PURE__ */ new Map();
  for (let index = 0; index < lines.length; index += 1) {
    const currentLine = lines[index] ?? "";
    const nextLine = lines[index + 1] ?? "";
    if (MARKDOWN_HEADING_PATTERN.test(currentLine) || HTML_HEADING_PATTERN.test(currentLine)) {
      boundaries.set(index, {
        startLine: index + 1,
        kind: inferBoundaryKind(lines, index),
        title: inferBoundaryTitle(lines, index)
      });
      continue;
    }
    if (hasText(currentLine) && UNDERLINE_HEADING_PATTERN.test(nextLine)) {
      boundaries.set(index, {
        startLine: index + 1,
        kind: "heading",
        title: normalizeWhitespace(currentLine)
      });
      continue;
    }
    if (INI_SECTION_PATTERN.test(currentLine)) {
      boundaries.set(index, {
        startLine: index + 1,
        kind: "section",
        title: inferBoundaryTitle(lines, index)
      });
      continue;
    }
    if (DELIMITER_PATTERN.test(currentLine)) {
      boundaries.set(index, {
        startLine: index + 1,
        kind: "delimiter",
        title: inferBoundaryTitle(lines, index)
      });
      continue;
    }
    for (const pattern of DECLARATION_PATTERNS) {
      if (pattern.test(currentLine)) {
        boundaries.set(index, {
          startLine: index + 1,
          kind: "declaration",
          title: inferBoundaryTitle(lines, index)
        });
        break;
      }
    }
  }
  if (!boundaries.has(0)) {
    boundaries.set(0, {
      startLine: 1,
      kind: "window",
      title: ""
    });
  }
  return [...boundaries.entries()].sort((left, right) => left[0] - right[0]).map(([, boundary]) => boundary);
}

// src/chunking.ts
var FALLBACK_CHUNK_LINES = 80;
var FALLBACK_CHUNK_OVERLAP = 20;
var STRUCTURE_MAX_SECTION_LINES = 160;
function splitLargeRangeIntoWindows(lines, startLine, endLine, inheritedTitle, inheritedKind) {
  const chunks = [];
  const totalLines = endLine - startLine + 1;
  if (totalLines <= STRUCTURE_MAX_SECTION_LINES) {
    chunks.push({
      startLine,
      endLine,
      kind: inheritedKind ?? "section",
      title: inheritedTitle ?? ""
    });
    return chunks;
  }
  let windowStart = startLine;
  let partIndex = 1;
  while (windowStart <= endLine) {
    const windowEnd = Math.min(endLine, windowStart + FALLBACK_CHUNK_LINES - 1);
    const partTitle = inheritedTitle ? `${inheritedTitle} (part ${partIndex})` : `window ${partIndex}`;
    chunks.push({
      startLine: windowStart,
      endLine: windowEnd,
      kind: inheritedKind === "window" ? "window" : `${inheritedKind ?? "section"}-part`,
      title: partTitle
    });
    if (windowEnd >= endLine) {
      break;
    }
    windowStart = Math.max(windowEnd - FALLBACK_CHUNK_OVERLAP + 1, windowStart + 1);
    partIndex += 1;
  }
  return chunks;
}

// src/chunkRanges.ts
function buildChunkRanges(lines) {
  if (lines.length === 0) {
    return [];
  }
  const boundaries = detectBoundaries(lines);
  if (boundaries.length <= 1) {
    return splitLargeRangeIntoWindows(lines, 1, lines.length, void 0, "window");
  }
  const chunkRanges = [];
  for (let index = 0; index < boundaries.length; index += 1) {
    const currentBoundary = boundaries[index];
    const nextBoundary = boundaries[index + 1];
    const startLine = currentBoundary.startLine;
    const endLine = nextBoundary ? nextBoundary.startLine - 1 : lines.length;
    if (startLine > endLine) {
      continue;
    }
    const splitRanges = splitLargeRangeIntoWindows(
      lines,
      startLine,
      endLine,
      currentBoundary.title,
      currentBoundary.kind
    );
    chunkRanges.push(...splitRanges);
  }
  return chunkRanges;
}

// src/projectMap/build/termCountsCache.ts
var TERM_COUNTS_SYMBOL = /* @__PURE__ */ Symbol("termCounts");
function setChunkTermCounts(chunk, counts) {
  chunk[TERM_COUNTS_SYMBOL] = counts;
}
function getChunkTermCounts(chunk) {
  return chunk[TERM_COUNTS_SYMBOL];
}

// src/buildChunkRecord.ts
import { performance } from "perf_hooks";

// src/extractKeyLikeLines.ts
function extractKeyLikeLines(lines, limit = 8) {
  const results = [];
  for (const line of lines) {
    const trimmed = line.trim();
    if (!trimmed) {
      continue;
    }
    if (/^[A-Za-z0-9 _.-]{2,60}:\s+/.test(trimmed) || /^[A-Za-z0-9_.-]+\s*=\s+/.test(trimmed)) {
      results.push(truncate(trimmed, 160));
    }
    if (results.length >= limit) {
      break;
    }
  }
  return results;
}

// src/extractReferencedPaths.ts
import path4 from "path";
function extractReferencedPaths(text, knownBasenamesSet) {
  const matches = String(text ?? "").match(/[A-Za-z0-9_./-]+\.[A-Za-z0-9]+/g) ?? [];
  const references = [];
  const seen = /* @__PURE__ */ new Set();
  for (const match of matches) {
    const normalized = match.replace(/^\.\//, "");
    const basename = path4.posix.basename(normalized);
    if (knownBasenamesSet && !knownBasenamesSet.has(basename)) {
      continue;
    }
    if (!seen.has(normalized)) {
      seen.add(normalized);
      references.push(normalized);
    }
    if (references.length >= 12) {
      break;
    }
  }
  return references;
}

// src/buildChunkRecord.ts
function buildChunkRecord({
  chunkId,
  fileId,
  relativeFilePath,
  lines,
  startLine,
  endLine,
  kind,
  title,
  knownBasenamesSet
}) {
  const SLOW_BUILD_CHUNK_RECORD_DIAGNOSTIC_THRESHOLD_MS = 1e3;
  const t0 = performance.now();
  const slice = lines.slice(startLine - 1, endLine);
  const t1 = performance.now();
  const text = slice.join("\n");
  const t2 = performance.now();
  const preview = buildPreviewFromLines(slice);
  const t3 = performance.now();
  const normalizedPreview = normalizeWhitespace(preview);
  const t4 = performance.now();
  const termCounts = countTokenizedTerms(text);
  const t5 = performance.now();
  const topTerms = topTermsFromCounts(termCounts);
  const t6 = performance.now();
  const identifiers = extractIdentifiers(text);
  const t7 = performance.now();
  const keyLikeLines = extractKeyLikeLines(slice);
  const t8 = performance.now();
  const quotedStrings = extractQuotedStrings(text);
  const t9 = performance.now();
  const referencedPaths = extractReferencedPaths(text, knownBasenamesSet);
  const t10 = performance.now();
  const tFinal = performance.now();
  const dur = (a, b) => Math.max(0, b - a);
  const sliceMs = dur(t0, t1);
  const joinMs = dur(t1, t2);
  const previewMs = dur(t2, t3);
  const previewNormMs = dur(t3, t4);
  const termCountMs = dur(t4, t5);
  const topTermsMs = dur(t5, t6);
  const identifiersMs = dur(t6, t7);
  const keyLikeLinesMs = dur(t7, t8);
  const quotedStringsMs = dur(t8, t9);
  const referencedPathsMs = dur(t9, t10);
  const remainingMs = dur(t10, tFinal);
  const totalMs = dur(t0, tFinal);
  if (totalMs >= SLOW_BUILD_CHUNK_RECORD_DIAGNOSTIC_THRESHOLD_MS) {
    console.log(`
=== SLOW BUILD CHUNK RECORD: ${relativeFilePath} ${chunkId} (${kind}) ===`);
    console.log(`title=${title || ""} start=${startLine} end=${endLine} lines=${slice.length} chars=${text.length} total_ms=${totalMs.toFixed(1)}`);
    console.log(`slice_ms=${sliceMs.toFixed(1)} join_ms=${joinMs.toFixed(1)} preview_ms=${previewMs.toFixed(1)} preview_norm_ms=${previewNormMs.toFixed(1)} term_count_ms=${termCountMs.toFixed(1)} top_terms_ms=${topTermsMs.toFixed(1)} identifiers_ms=${identifiersMs.toFixed(1)} keylike_ms=${keyLikeLinesMs.toFixed(1)} quoted_ms=${quotedStringsMs.toFixed(1)} referenced_ms=${referencedPathsMs.toFixed(1)} remaining_ms=${remainingMs.toFixed(1)}`);
    console.log(`termCounts=${termCounts.size} topTerms=${topTerms.length} identifiers=${identifiers.length} keyLikeLines=${keyLikeLines.length} quotedStrings=${quotedStrings.length} referencedPaths=${referencedPaths.length}`);
    console.log("=== END SLOW BUILD CHUNK RECORD ===\n");
  }
  const out = {
    chunk_id: chunkId,
    file_id: fileId,
    path: relativeFilePath,
    start_line: startLine,
    end_line: endLine,
    kind,
    title: title || "",
    preview: normalizedPreview,
    text,
    line_count: endLine - startLine + 1,
    top_terms: topTerms,
    top_identifiers: identifiers,
    key_like_lines: keyLikeLines,
    quoted_strings: quotedStrings,
    referenced_paths: referencedPaths
  };
  try {
    setChunkTermCounts(out, termCounts);
  } catch (e) {
  }
  return out;
}

// src/chunkTextFile.ts
import { performance as performance2 } from "node:perf_hooks";
var SLOW_CHUNK_FILE_DIAGNOSTIC_THRESHOLD_MS = 1e3;
function chunkTextFile({
  fileId,
  relativeFilePath,
  text,
  knownBasenamesSet,
  chunkIdGenerator
}) {
  const totalStart = performance2.now();
  const lineStart = performance2.now();
  const lines = text.split(/\r?\n/);
  const lineElapsed = performance2.now() - lineStart;
  const boundaryStart = performance2.now();
  const chunkRanges = buildChunkRanges(lines);
  const boundaryElapsed = performance2.now() - boundaryStart;
  const chunks = [];
  const loopStart = performance2.now();
  let totalBuildRecordMs = 0;
  let maxBuildRecordMs = 0;
  let slowestChunkInfo = null;
  for (const chunkRange of chunkRanges) {
    const chunkId = chunkIdGenerator();
    const buildStart = performance2.now();
    const rec = buildChunkRecord({
      chunkId,
      fileId,
      relativeFilePath,
      lines,
      startLine: chunkRange.startLine,
      endLine: chunkRange.endLine,
      kind: chunkRange.kind,
      title: chunkRange.title,
      knownBasenamesSet
    });
    const buildElapsed = performance2.now() - buildStart;
    totalBuildRecordMs += buildElapsed;
    if (buildElapsed > maxBuildRecordMs) {
      maxBuildRecordMs = buildElapsed;
      slowestChunkInfo = {
        title: rec.title,
        kind: rec.kind,
        startLine: rec.start_line,
        endLine: rec.end_line,
        elapsedMs: buildElapsed
      };
    }
    chunks.push(rec);
  }
  const loopElapsed = performance2.now() - loopStart;
  const chunkRangeConstructionMs = Math.max(0, loopElapsed - totalBuildRecordMs);
  const totalElapsed = performance2.now() - totalStart;
  const remainingOverhead = Math.max(0, totalElapsed - (lineElapsed + boundaryElapsed + loopElapsed));
  if (totalElapsed >= SLOW_CHUNK_FILE_DIAGNOSTIC_THRESHOLD_MS) {
    const charCount = text.length;
    const lineCount = lines.length;
    const boundaryCount = chunkRanges.length;
    const chunkRangeCount = chunkRanges.length;
    const finalChunkCount = chunks.length;
    const avgBuildMs = finalChunkCount > 0 ? totalBuildRecordMs / finalChunkCount : 0;
    console.log("SLOW CHUNK FILE DIAGNOSTIC");
    console.log(`- path: ${relativeFilePath}`);
    console.log(`- size_chars: ${charCount} chars | lines: ${lineCount}`);
    console.log(`- boundaries: ${boundaryCount} | chunk_ranges: ${chunkRangeCount} | final_chunks: ${finalChunkCount}`);
    console.log(`- totals: ${totalElapsed.toFixed(1)} ms (lines=${lineElapsed.toFixed(1)} ms boundaries=${boundaryElapsed.toFixed(1)} ms chunk_ranges=${chunkRangeConstructionMs.toFixed(1)} ms build_records_total=${totalBuildRecordMs.toFixed(1)} ms remaining_overhead=${remainingOverhead.toFixed(1)} ms)`);
    console.log(`- build_records: total=${totalBuildRecordMs.toFixed(1)} ms avg=${avgBuildMs.toFixed(1)} ms max=${maxBuildRecordMs.toFixed(1)} ms`);
    if (slowestChunkInfo) {
      console.log(`- slowest_chunk: ${slowestChunkInfo.elapsedMs.toFixed(1)} ms | kind=${slowestChunkInfo.kind} | title=${String(slowestChunkInfo.title || "")} | start=${slowestChunkInfo.startLine} end=${slowestChunkInfo.endLine}`);
    }
  }
  return { lines, chunks };
}

// src/projectMap/build/postings.ts
import path5 from "path";
function createPostingsAccumulator() {
  return /* @__PURE__ */ new Map();
}
function addChunkToPostings(postings, chunkRecord) {
  const cached = getChunkTermCounts(chunkRecord);
  const fullCounts = cached ?? countTokenizedTerms(chunkRecord.text);
  for (const [term, tf] of fullCounts.entries()) {
    const bucket = bucketForTerm(term);
    if (!postings.has(bucket)) {
      postings.set(bucket, /* @__PURE__ */ new Map());
    }
    const bucketMap = postings.get(bucket);
    if (!bucketMap.has(term)) {
      bucketMap.set(term, []);
    }
    bucketMap.get(term).push({ chunk_id: chunkRecord.chunk_id, tf });
  }
}
async function persistPostings(postings, postingsDir) {
  for (const [bucket, bucketMap] of postings.entries()) {
    const bucketObject = {};
    const sortedTerms = [...bucketMap.keys()].sort((left, right) => left.localeCompare(right));
    for (const term of sortedTerms) {
      bucketObject[term] = bucketMap.get(term);
    }
    await writeJson(path5.join(postingsDir, `${bucket}.json`), bucketObject);
  }
}

// src/projectMap/build/records.ts
function buildIndexedFileRecord({
  fileId,
  relativeFilePath,
  extension,
  sizeBytes,
  mtimeMs,
  fileClass,
  text,
  lines,
  chunks
}) {
  const fileTermCounts = /* @__PURE__ */ new Map();
  const titles = [];
  const preview = buildPreviewFromLines(lines);
  for (const chunk of chunks) {
    for (const { term, count } of chunk.top_terms ?? []) {
      fileTermCounts.set(term, (fileTermCounts.get(term) ?? 0) + count);
    }
    if (chunk.title && chunk.title.trim().length > 0) {
      titles.push(chunk.title);
    }
  }
  const fileIdentifiers = extractIdentifiers(text);
  return {
    file_id: fileId,
    path: relativeFilePath,
    extension,
    size_bytes: sizeBytes,
    mtime_ms: mtimeMs,
    indexed: true,
    file_class: fileClass,
    line_count: lines.length,
    chunk_count: chunks.length,
    chunk_ids: chunks.map((chunk) => chunk.chunk_id),
    section_titles: [...new Set(titles)].slice(0, 24),
    top_terms: topTermsFromCounts(fileTermCounts, 20),
    top_identifiers: fileIdentifiers,
    preview
  };
}
function buildSkippedFileRecord({
  fileId,
  relativeFilePath,
  extension,
  sizeBytes,
  mtimeMs,
  fileClass,
  skipReason
}) {
  return {
    file_id: fileId,
    path: relativeFilePath,
    extension,
    size_bytes: sizeBytes,
    mtime_ms: mtimeMs,
    indexed: false,
    file_class: fileClass,
    line_count: 0,
    chunk_count: 0,
    chunk_ids: [],
    section_titles: [],
    top_terms: [],
    top_identifiers: [],
    preview: "",
    skip_reason: skipReason
  };
}
function buildRepoTopTerms(fileRecords) {
  const termCounts = /* @__PURE__ */ new Map();
  for (const fileRecord of fileRecords) {
    if (!fileRecord.indexed) {
      continue;
    }
    for (const { term, count } of fileRecord.top_terms ?? []) {
      termCounts.set(term, (termCounts.get(term) ?? 0) + count);
    }
  }
  return topTermsFromCounts(termCounts, 30);
}
function buildDirectoryRecords(fileRecords) {
  const directoryMap = /* @__PURE__ */ new Map();
  let directoryCounter = 0;
  const getOrCreateDirectory = (dirPath) => {
    if (!directoryMap.has(dirPath)) {
      directoryCounter += 1;
      directoryMap.set(dirPath, {
        dir_id: `d${String(directoryCounter).padStart(6, "0")}`,
        path: dirPath,
        recursive_file_count: 0,
        indexed_file_count: 0,
        total_size_bytes: 0,
        extension_counts: /* @__PURE__ */ Object.create(null),
        class_counts: /* @__PURE__ */ Object.create(null),
        term_counts: /* @__PURE__ */ new Map(),
        notable_files: []
      });
    }
    return directoryMap.get(dirPath);
  };
  getOrCreateDirectory(".");
  for (const fileRecord of fileRecords) {
    const directories = fileRecord.path.split("/");
    directories.pop();
    const dirs = ["."];
    let current = "";
    for (const part of directories) {
      current = current ? `${current}/${part}` : part;
      dirs.push(current);
    }
    for (const dirPath of dirs) {
      const dirAccumulator = getOrCreateDirectory(dirPath);
      dirAccumulator.recursive_file_count += 1;
      dirAccumulator.total_size_bytes += fileRecord.size_bytes;
      dirAccumulator.extension_counts[fileRecord.extension || "(none)"] = (dirAccumulator.extension_counts[fileRecord.extension || "(none)"] ?? 0) + 1;
      dirAccumulator.class_counts[fileRecord.file_class] = (dirAccumulator.class_counts[fileRecord.file_class] ?? 0) + 1;
      if (fileRecord.indexed) {
        dirAccumulator.indexed_file_count += 1;
        for (const { term, count } of fileRecord.top_terms ?? []) {
          dirAccumulator.term_counts.set(term, (dirAccumulator.term_counts.get(term) ?? 0) + count);
        }
      }
      if (dirAccumulator.notable_files.length < 12) {
        dirAccumulator.notable_files.push({
          path: fileRecord.path,
          indexed: fileRecord.indexed,
          file_class: fileRecord.file_class,
          chunk_count: fileRecord.chunk_count
        });
      }
    }
  }
  const directoryRecords = [...directoryMap.values()].map((directoryRecord) => ({
    dir_id: directoryRecord.dir_id,
    path: directoryRecord.path,
    recursive_file_count: directoryRecord.recursive_file_count,
    indexed_file_count: directoryRecord.indexed_file_count,
    total_size_bytes: directoryRecord.total_size_bytes,
    extension_counts: (function sortCounterObject2(obj, limit = 15) {
      const entries = Object.entries(obj).sort((left, right) => {
        const countDelta = right[1] - left[1];
        if (countDelta !== 0) {
          return countDelta;
        }
        return left[0].localeCompare(right[0]);
      });
      const limitedEntries = limit == null ? entries : entries.slice(0, limit);
      return Object.fromEntries(limitedEntries);
    })(directoryRecord.extension_counts, 15),
    class_counts: (function sortCounterObject2(obj, limit = 15) {
      const entries = Object.entries(obj).sort((left, right) => {
        const countDelta = right[1] - left[1];
        if (countDelta !== 0) {
          return countDelta;
        }
        return left[0].localeCompare(right[0]);
      });
      const limitedEntries = limit == null ? entries : entries.slice(0, limit);
      return Object.fromEntries(limitedEntries);
    })(directoryRecord.class_counts, 15),
    top_terms: (function topTermsFromCounts2(termCounts, limit = 20) {
      return [...termCounts.entries()].sort((l, r) => {
        const d = r[1] - l[1];
        if (d !== 0) {
          return d;
        }
        return l[0].localeCompare(r[0]);
      }).slice(0, limit).map(([term, count]) => ({ term, count }));
    })(directoryRecord.term_counts, 20),
    notable_files: directoryRecord.notable_files.sort((left, right) => left.path.localeCompare(right.path))
  })).sort((left, right) => left.path.localeCompare(right.path));
  return directoryRecords;
}

// src/projectMap/build/utils.ts
import path6 from "path";
function incrementCounterObject(counterObject, key, incrementBy = 1) {
  counterObject[key] = (counterObject[key] ?? 0) + incrementBy;
}
function buildKnownBasenamesSet(filePaths) {
  const basenames = /* @__PURE__ */ new Set();
  for (const filePathValue of filePaths) {
    basenames.add(path6.posix.basename(filePathValue));
  }
  return basenames;
}
function sortCounterObject(counterObject, limit = null) {
  const entries = Object.entries(counterObject).sort((left, right) => {
    const countDelta = right[1] - left[1];
    if (countDelta !== 0) {
      return countDelta;
    }
    return left[0].localeCompare(right[0]);
  });
  const limitedEntries = limit == null ? entries : entries.slice(0, limit);
  return Object.fromEntries(limitedEntries);
}

// src/projectMap/build/collect.ts
async function collectProjectFiles(projectRoot) {
  const { PROJECT_ROOT } = getPaths(projectRoot);
  const results = [];
  async function walk(absoluteDirectoryPath) {
    const entries = await fs3.readdir(absoluteDirectoryPath, { withFileTypes: true });
    entries.sort((left, right) => left.name.localeCompare(right.name));
    for (const entry of entries) {
      const absoluteEntryPath = path7.join(absoluteDirectoryPath, entry.name);
      const relativeEntryPath = toRelativeProjectPath(absoluteEntryPath, PROJECT_ROOT);
      if (entry.isDirectory()) {
        if (shouldIgnoreDirectory(relativeEntryPath, entry.name)) {
          continue;
        }
        await walk(absoluteEntryPath);
        continue;
      }
      if (!entry.isFile()) {
        continue;
      }
      results.push({ absolute_path: absoluteEntryPath, relative_path: relativeEntryPath });
    }
  }
  await walk(PROJECT_ROOT);
  return results;
}
async function runBuild(projectRoot) {
  const paths = getPaths(projectRoot);
  const overallStart = performance3.now();
  console.log("PROJECT MAP BUILD STARTED");
  console.log(`project_root: ${paths.PROJECT_ROOT}`);
  console.log("preparing state...");
  const setupStart = performance3.now();
  await ensureScaleDirectory(paths.AI_DIR);
  await removeDirectoryIfPresent(paths.STATE_DIR);
  await ensureStateDirectories(paths);
  const setupElapsed = performance3.now() - setupStart;
  console.log(`prepared state (${setupElapsed.toFixed(1)} ms)`);
  const buildStartedAt = (/* @__PURE__ */ new Date()).toISOString();
  console.log("discovering files...");
  const discoveryStart = performance3.now();
  const discoveredFiles = await collectProjectFiles(projectRoot);
  const discoveryElapsed = performance3.now() - discoveryStart;
  console.log(`discovered_files: ${discoveredFiles.length} (${discoveryElapsed.toFixed(1)} ms)`);
  const knownBasenamesSet = buildKnownBasenamesSet(discoveredFiles.map((file) => file.relative_path));
  const fileRecords = [];
  const chunkRecords = [];
  const postings = createPostingsAccumulator();
  let indexedTextFiles = 0;
  let skippedFiles = 0;
  let binaryFiles = 0;
  let generatedFiles = 0;
  let fileCounter = 0;
  let chunkCounter = 0;
  const nextFileId = () => {
    fileCounter += 1;
    return `f${String(fileCounter).padStart(6, "0")}`;
  };
  const nextChunkId = () => {
    chunkCounter += 1;
    return `c${String(chunkCounter).padStart(7, "0")}`;
  };
  async function mapWithConcurrency(items, worker2, concurrency) {
    const results2 = new Array(items.length);
    let nextIndex = 0;
    let active = 0;
    return await new Promise((resolve, reject) => {
      function launch() {
        if (nextIndex >= items.length && active === 0) {
          resolve(results2);
          return;
        }
        while (active < concurrency && nextIndex < items.length) {
          const idx = nextIndex++;
          active += 1;
          Promise.resolve().then(() => worker2(items[idx], idx)).then((res) => {
            results2[idx] = res;
            active -= 1;
            launch();
          }).catch((err) => reject(err));
        }
      }
      launch();
    });
  }
  const processedFiles = [];
  const fileIds = discoveredFiles.map(() => nextFileId());
  const available = typeof os.availableParallelism === "function" ? os.availableParallelism() : os.cpus().length;
  const CONCURRENCY = Math.max(1, Math.min(DEFAULT_BUILD_CONCURRENCY_LIMIT, Number(available) || 1));
  const WRITE_CONCURRENCY = DEFAULT_BUILD_WRITE_CONCURRENCY_LIMIT;
  const worker = (discoveredFile, index) => {
    return processDiscoveredFileForBuild({
      discoveredFile,
      fileId: fileIds[index],
      knownBasenamesSet
    });
  };
  console.log(`processing files: ${discoveredFiles.length} (concurrency=${CONCURRENCY})...`);
  const fileProcessingStart = performance3.now();
  const results = await mapWithConcurrency(discoveredFiles, worker, CONCURRENCY);
  for (const r of results) processedFiles.push(r);
  const fileProcessingElapsed = performance3.now() - fileProcessingStart;
  console.log(`file processing work: ${fileProcessingElapsed.toFixed(1)} ms`);
  const workerTotals = processedFiles.reduce((acc, pf) => {
    const t = pf.timings || { metadataMs: 0, readMs: 0, chunkMs: 0, recordMs: 0, path: "", sizeBytes: 0, indexed: false, fileClass: "", chunkCount: 0, totalMs: 0 };
    acc.metadataMs += t.metadataMs;
    acc.readMs += t.readMs;
    acc.chunkMs += t.chunkMs;
    acc.recordMs += t.recordMs;
    return acc;
  }, { metadataMs: 0, readMs: 0, chunkMs: 0, recordMs: 0 });
  console.log(`file worker totals: metadata=${workerTotals.metadataMs.toFixed(1)} ms read=${workerTotals.readMs.toFixed(1)} ms chunk=${workerTotals.chunkMs.toFixed(1)} ms record=${workerTotals.recordMs.toFixed(1)} ms`);
  const TOP_SLOW_FILE_DIAGNOSTIC_LIMIT = 20;
  const diagnostics = processedFiles.map((pf) => pf.timings);
  const chunkingTop = diagnostics.map((t) => ({
    path: String(t.path || ""),
    sizeBytes: Number(t.sizeBytes || 0),
    indexed: Boolean(t.indexed),
    fileClass: String(t.fileClass || ""),
    chunkCount: Number(t.chunkCount || 0),
    metadataMs: Number(t.metadataMs || 0),
    readMs: Number(t.readMs || 0),
    chunkMs: Number(t.chunkMs || 0),
    recordMs: Number(t.recordMs || 0),
    totalMs: Number(t.totalMs || 0)
  })).sort((a, b) => {
    const d = b.chunkMs - a.chunkMs;
    if (d !== 0) return d;
    const d2 = b.totalMs - a.totalMs;
    if (d2 !== 0) return d2;
    return a.path.localeCompare(b.path);
  }).slice(0, TOP_SLOW_FILE_DIAGNOSTIC_LIMIT);
  if (chunkingTop.length > 0) {
    console.log("TOP SLOW FILES BY CHUNKING");
    for (const entry of chunkingTop) {
      console.log(`- ${entry.chunkMs.toFixed(1)} ms chunk | ${entry.chunkCount} chunks | ${entry.sizeBytes} bytes | ${entry.path}`);
    }
  }
  const workerTop = diagnostics.map((t) => ({
    path: String(t.path || ""),
    sizeBytes: Number(t.sizeBytes || 0),
    indexed: Boolean(t.indexed),
    fileClass: String(t.fileClass || ""),
    chunkCount: Number(t.chunkCount || 0),
    metadataMs: Number(t.metadataMs || 0),
    readMs: Number(t.readMs || 0),
    chunkMs: Number(t.chunkMs || 0),
    recordMs: Number(t.recordMs || 0),
    totalMs: Number(t.totalMs || 0)
  })).sort((a, b) => {
    const d = b.totalMs - a.totalMs;
    if (d !== 0) return d;
    const d2 = b.chunkMs - a.chunkMs;
    if (d2 !== 0) return d2;
    return a.path.localeCompare(b.path);
  }).slice(0, TOP_SLOW_FILE_DIAGNOSTIC_LIMIT);
  if (workerTop.length > 0) {
    console.log("TOP SLOW FILES BY WORKER TIME");
    for (const entry of workerTop) {
      console.log(`- ${entry.totalMs.toFixed(1)} ms total | metadata=${entry.metadataMs.toFixed(1)} read=${entry.readMs.toFixed(1)} chunk=${entry.chunkMs.toFixed(1)} record=${entry.recordMs.toFixed(1)} ms | ${entry.chunkCount} chunks | ${entry.sizeBytes} bytes | ${entry.path}`);
    }
  }
  const mergeStart = performance3.now();
  const counterAggStart = performance3.now();
  for (const processed of processedFiles) {
    indexedTextFiles += processed.deltas.indexedTextFiles;
    skippedFiles += processed.deltas.skippedFiles;
    binaryFiles += processed.deltas.binaryFiles;
    generatedFiles += processed.deltas.generatedFiles;
  }
  const counterAggElapsed = performance3.now() - counterAggStart;
  console.log(`counter aggregation work: ${counterAggElapsed.toFixed(1)} ms`);
  const chunkFinalizeStart = performance3.now();
  for (const processed of processedFiles) {
    const localToFinal = {};
    for (const chunk of processed.chunkRecords) {
      const finalId = nextChunkId();
      localToFinal[chunk.chunk_id] = finalId;
      chunk.chunk_id = finalId;
      chunkRecords.push(chunk);
    }
    if (processed.fileRecord && Array.isArray(processed.fileRecord.chunk_ids)) {
      processed.fileRecord = {
        ...processed.fileRecord,
        chunk_ids: processed.fileRecord.chunk_ids.map((id) => localToFinal[id] ?? id)
      };
    }
    fileRecords.push(processed.fileRecord);
  }
  const chunkFinalizeElapsed = performance3.now() - chunkFinalizeStart;
  console.log(`chunk finalization work: ${chunkFinalizeElapsed.toFixed(1)} ms`);
  const postingsAccumStart = performance3.now();
  for (const chunk of chunkRecords) {
    addChunkToPostings(postings, chunk);
  }
  const postingsAccumElapsed = performance3.now() - postingsAccumStart;
  console.log(`postings accumulation work: ${postingsAccumElapsed.toFixed(1)} ms`);
  const mergeElapsed = performance3.now() - mergeStart;
  console.log(`merge/postings work: ${mergeElapsed.toFixed(1)} ms`);
  const summaryStart = performance3.now();
  const directoryRecords = buildDirectoryRecords(fileRecords);
  const extensionCounts = {};
  const classCounts = {};
  for (const fileRecord of fileRecords) {
    incrementCounterObject(extensionCounts, fileRecord.extension || "(none)");
    incrementCounterObject(classCounts, fileRecord.file_class);
  }
  const repoSynopsis = {
    project_root: paths.PROJECT_ROOT,
    project_root_relative_hint: ".",
    built_at: (/* @__PURE__ */ new Date()).toISOString(),
    version: PROJECT_MAP_VERSION,
    total_files_seen: fileRecords.length,
    indexed_text_files: indexedTextFiles,
    skipped_files: skippedFiles,
    binary_files: binaryFiles,
    generated_files_skipped: generatedFiles,
    total_chunks: chunkRecords.length,
    major_extensions: sortCounterObject(extensionCounts, 20),
    major_file_classes: sortCounterObject(classCounts, 20),
    top_terms: buildRepoTopTerms(fileRecords),
    largest_indexed_text_files: fileRecords.filter((fr) => fr.indexed).sort((l, r) => r.size_bytes - l.size_bytes).slice(0, 20).map((fr) => ({
      path: fr.path,
      size_bytes: fr.size_bytes,
      chunk_count: fr.chunk_count,
      file_class: fr.file_class
    })),
    major_directories: directoryRecords.filter((d) => d.path !== ".").sort((l, r) => r.recursive_file_count - l.recursive_file_count || l.path.localeCompare(r.path)).slice(0, 20).map((d) => ({
      path: d.path,
      recursive_file_count: d.recursive_file_count,
      indexed_file_count: d.indexed_file_count
    }))
  };
  const buildInfo = {
    version: PROJECT_MAP_VERSION,
    build_started_at: buildStartedAt,
    build_finished_at: (/* @__PURE__ */ new Date()).toISOString(),
    project_root: paths.PROJECT_ROOT,
    total_files_seen: fileRecords.length,
    indexed_text_files: indexedTextFiles,
    skipped_files: skippedFiles,
    total_chunks: chunkRecords.length
  };
  const summaryElapsed = performance3.now() - summaryStart;
  console.log(`summary work: ${summaryElapsed.toFixed(1)} ms`);
  const processingElapsed = fileProcessingElapsed + mergeElapsed + summaryElapsed;
  console.log(`processed files: indexed=${indexedTextFiles} skipped=${skippedFiles} chunks=${chunkRecords.length} (${processingElapsed.toFixed(1)} ms)`);
  console.log(`writing state... (writeConcurrency=${WRITE_CONCURRENCY})`);
  const writeStart = performance3.now();
  const corePromise = (async () => {
    const coreStart = performance3.now();
    await writeJson(path7.join(paths.STATE_DIR, "build.json"), buildInfo);
    await writeJson(path7.join(paths.STATE_DIR, "repo.json"), repoSynopsis);
    await writeJsonLines(path7.join(paths.STATE_DIR, "dirs.jsonl"), directoryRecords);
    await writeJsonLines(path7.join(paths.STATE_DIR, "files.jsonl"), fileRecords);
    await writeJsonLines(path7.join(paths.STATE_DIR, "chunks.jsonl"), chunkRecords);
    const coreElapsed = performance3.now() - coreStart;
    console.log(`core state write: ${coreElapsed.toFixed(1)} ms`);
    return coreElapsed;
  })();
  const postingsPromise = (async () => {
    const postingsStart = performance3.now();
    await persistPostings(postings, paths.POSTINGS_DIR);
    const postingsElapsed = performance3.now() - postingsStart;
    console.log(`postings write: ${postingsElapsed.toFixed(1)} ms`);
    return postingsElapsed;
  })();
  const synopsisPromise = (async () => {
    const synopsisStart = performance3.now();
    await writeJson(path7.join(paths.SYNOPSES_DIR, "repo.json"), repoSynopsis);
    await mapWithConcurrency(directoryRecords, async (directoryRecord) => {
      return writeJson(path7.join(paths.SYNOPSES_DIRS_DIR, `${directoryRecord.dir_id}.json`), directoryRecord);
    }, WRITE_CONCURRENCY);
    await mapWithConcurrency(fileRecords, async (fileRecord) => {
      return writeJson(path7.join(paths.SYNOPSES_FILES_DIR, `${fileRecord.file_id}.json`), fileRecord);
    }, WRITE_CONCURRENCY);
    const synopsisElapsed = performance3.now() - synopsisStart;
    console.log(`synopsis write: ${synopsisElapsed.toFixed(1)} ms (writeConcurrency=${WRITE_CONCURRENCY})`);
    return synopsisElapsed;
  })();
  await Promise.all([corePromise, postingsPromise, synopsisPromise]);
  const writeElapsed = performance3.now() - writeStart;
  console.log(`wrote state (${writeElapsed.toFixed(1)} ms)`);
  const totalElapsed = performance3.now() - overallStart;
  printBuildSummary(buildInfo, repoSynopsis, totalElapsed);
  return { buildInfo, repoSynopsis, directoryRecords, fileRecords, chunkRecords };
}
async function processDiscoveredFileForBuild(opts) {
  const { discoveredFile, fileId, knownBasenamesSet } = opts;
  const metaStart = performance3.now();
  const stats = await fs3.stat(discoveredFile.absolute_path);
  const extension = path7.extname(discoveredFile.relative_path).toLowerCase();
  const textFile = await isTextFile(discoveredFile.absolute_path, extension);
  const fileClass = classifyFile(discoveredFile.relative_path, extension, textFile);
  const metadataMs = performance3.now() - metaStart;
  const deltas = { indexedTextFiles: 0, skippedFiles: 0, binaryFiles: 0, generatedFiles: 0 };
  let fileRecord = null;
  const chunkRecords = [];
  let readMs = 0;
  let chunkMs = 0;
  let recordMs = 0;
  if (!textFile) {
    deltas.binaryFiles = 1;
    deltas.skippedFiles = 1;
    const recStart2 = performance3.now();
    fileRecord = buildSkippedFileRecord({
      fileId,
      relativeFilePath: discoveredFile.relative_path,
      extension,
      sizeBytes: stats.size,
      mtimeMs: stats.mtimeMs,
      fileClass,
      skipReason: "binary-or-asset"
    });
    recordMs = performance3.now() - recStart2;
    return {
      fileRecord,
      chunkRecords,
      deltas,
      timings: {
        metadataMs,
        readMs,
        chunkMs,
        recordMs,
        path: discoveredFile.relative_path,
        sizeBytes: stats.size,
        indexed: false,
        fileClass,
        chunkCount: 0,
        totalMs: metadataMs + readMs + chunkMs + recordMs
      }
    };
  }
  if (fileClass === "generated") {
    deltas.generatedFiles = 1;
    deltas.skippedFiles = 1;
    const recStart2 = performance3.now();
    fileRecord = buildSkippedFileRecord({
      fileId,
      relativeFilePath: discoveredFile.relative_path,
      extension,
      sizeBytes: stats.size,
      mtimeMs: stats.mtimeMs,
      fileClass,
      skipReason: "generated-noise"
    });
    recordMs = performance3.now() - recStart2;
    return {
      fileRecord,
      chunkRecords,
      deltas,
      timings: {
        metadataMs,
        readMs,
        chunkMs,
        recordMs,
        path: discoveredFile.relative_path,
        sizeBytes: stats.size,
        indexed: false,
        fileClass,
        chunkCount: 0,
        totalMs: metadataMs + readMs + chunkMs + recordMs
      }
    };
  }
  const readStart = performance3.now();
  const text = await fs3.readFile(discoveredFile.absolute_path, "utf8");
  readMs = performance3.now() - readStart;
  if (hasLineLongerThan(text, DEFAULT_MAX_INDEXABLE_LINE_LENGTH)) {
    deltas.skippedFiles = 1;
    const recStartLongLine = performance3.now();
    fileRecord = buildSkippedFileRecord({
      fileId,
      relativeFilePath: discoveredFile.relative_path,
      extension,
      sizeBytes: stats.size,
      mtimeMs: stats.mtimeMs,
      fileClass,
      skipReason: "minified-or-long-line"
    });
    recordMs = performance3.now() - recStartLongLine;
    return {
      fileRecord,
      chunkRecords,
      deltas,
      timings: {
        metadataMs,
        readMs,
        chunkMs,
        recordMs,
        path: discoveredFile.relative_path,
        sizeBytes: stats.size,
        indexed: false,
        fileClass,
        chunkCount: 0,
        totalMs: metadataMs + readMs + chunkMs + recordMs
      }
    };
  }
  let localChunkCounter = 0;
  const localChunkId = () => {
    localChunkCounter += 1;
    return `local-c${String(localChunkCounter).padStart(6, "0")}`;
  };
  const chunkStart = performance3.now();
  const { lines, chunks } = chunkTextFile({
    fileId,
    relativeFilePath: discoveredFile.relative_path,
    text,
    knownBasenamesSet,
    chunkIdGenerator: localChunkId
  });
  chunkMs = performance3.now() - chunkStart;
  for (const chunk of chunks) {
    chunkRecords.push(chunk);
  }
  const recStart = performance3.now();
  fileRecord = buildIndexedFileRecord({
    fileId,
    relativeFilePath: discoveredFile.relative_path,
    extension,
    sizeBytes: stats.size,
    mtimeMs: stats.mtimeMs,
    fileClass,
    text,
    lines,
    chunks
  });
  recordMs = performance3.now() - recStart;
  deltas.indexedTextFiles = 1;
  const chunkCount = chunkRecords.length;
  return {
    fileRecord,
    chunkRecords,
    deltas,
    timings: {
      metadataMs,
      readMs,
      chunkMs,
      recordMs,
      path: discoveredFile.relative_path,
      sizeBytes: stats.size,
      indexed: true,
      fileClass,
      chunkCount,
      totalMs: metadataMs + readMs + chunkMs + recordMs
    }
  };
}
function printBuildSummary(buildInfo, repoSynopsis, totalMs) {
  console.log("PROJECT MAP BUILD COMPLETE");
  if (typeof totalMs === "number") {
    console.log(`total_time: ${(totalMs / 1e3).toFixed(2)}s (${totalMs.toFixed(1)} ms)`);
  }
  console.log(`version: ${buildInfo.version}`);
  console.log(`project_root: ${buildInfo.project_root}`);
  console.log(`built_at: ${buildInfo.build_finished_at}`);
  console.log(`total_files_seen: ${buildInfo.total_files_seen}`);
  console.log(`indexed_text_files: ${buildInfo.indexed_text_files}`);
  console.log(`skipped_files: ${buildInfo.skipped_files}`);
  console.log(`total_chunks: ${buildInfo.total_chunks}`);
  console.log("");
  console.log("TOP DIRECTORIES");
  for (const directory of repoSynopsis.major_directories.slice(0, 10)) {
    console.log(`- ${directory.path} (files=${directory.recursive_file_count}, indexed=${directory.indexed_file_count})`);
  }
}

// src/projectMap/state.ts
import path8 from "path";
import fs4 from "node:fs/promises";
async function assertStatePresent(projectRoot) {
  const paths = getPaths(projectRoot);
  try {
    await fs4.access(path8.join(paths.STATE_DIR, "build.json"));
    await fs4.access(path8.join(paths.STATE_DIR, "repo.json"));
    await fs4.access(path8.join(paths.STATE_DIR, "files.jsonl"));
    await fs4.access(path8.join(paths.STATE_DIR, "chunks.jsonl"));
  } catch {
    throw new Error("ProjectMap state is missing or incomplete. Run: node .ai/scale/project-map.mjs build");
  }
}
async function loadCoreState(projectRoot) {
  await assertStatePresent(projectRoot);
  const paths = getPaths(projectRoot);
  const [buildInfo, repoInfo, fileRecords, chunkRecords, directoryRecords] = await Promise.all([
    readJson(path8.join(paths.STATE_DIR, "build.json")),
    readJson(path8.join(paths.STATE_DIR, "repo.json")),
    readJsonLines(path8.join(paths.STATE_DIR, "files.jsonl")),
    readJsonLines(path8.join(paths.STATE_DIR, "chunks.jsonl")),
    readJsonLines(path8.join(paths.STATE_DIR, "dirs.jsonl")).catch(() => [])
  ]);
  const filesById = /* @__PURE__ */ new Map();
  const filesByPath = /* @__PURE__ */ new Map();
  for (const fileRecord of fileRecords) {
    filesById.set(fileRecord.file_id, fileRecord);
    filesByPath.set(fileRecord.path, fileRecord);
  }
  const chunksById = /* @__PURE__ */ new Map();
  const chunksByFileId = /* @__PURE__ */ new Map();
  for (const chunkRecord of chunkRecords) {
    chunksById.set(chunkRecord.chunk_id, chunkRecord);
    if (!chunksByFileId.has(chunkRecord.file_id)) {
      chunksByFileId.set(chunkRecord.file_id, []);
    }
    chunksByFileId.get(chunkRecord.file_id).push(chunkRecord);
  }
  const dirsById = /* @__PURE__ */ new Map();
  const dirsByPath = /* @__PURE__ */ new Map();
  for (const directoryRecord of directoryRecords) {
    dirsById.set(directoryRecord.dir_id, directoryRecord);
    dirsByPath.set(directoryRecord.path, directoryRecord);
  }
  return {
    buildInfo,
    repoInfo,
    fileRecords,
    chunkRecords,
    directoryRecords,
    filesById,
    filesByPath,
    chunksById,
    chunksByFileId,
    dirsById,
    dirsByPath
  };
}

// src/projectMap/query/core.ts
var core_exports = {};
__export(core_exports, {
  loadRelevantPostings: () => loadRelevantPostings,
  makePersistableQueryResult: () => makePersistableQueryResult,
  normalizeQuery: () => normalizeQuery,
  persistQueryArtifact: () => persistQueryArtifact,
  runQuery: () => runQuery,
  scoreChunkForQuery: () => scoreChunkForQuery
});
import path9 from "path";
var QUERY_ARTIFACT_SLUG_MAX_LENGTH = 80;
function normalizeRepoPath(value) {
  return String(value ?? "").replace(/\\/g, "/").replace(/^\.\//, "");
}
function isTestLikePath(filePath, fileClass) {
  return fileClass === "test" || /(^|\/)(__tests__|tests?|test)(\/|$)/i.test(filePath) || /\.test\./i.test(filePath) || /\.spec\./i.test(filePath);
}
function pathStem(filePath) {
  const normalized = normalizeRepoPath(filePath);
  const baseName = path9.posix.basename(normalized).replace(/\.[^.\/]+$/, "").replace(/\.(test|spec)$/i, "");
  return baseName.toLowerCase();
}
function pathStemTokens(filePath) {
  return pathStem(filePath).split(/[^a-z0-9]+/i).map((token) => token.toLowerCase()).filter((token) => token.length > 1);
}
function titleTokens(value) {
  return tokenizeText(value).map((token) => token.toLowerCase());
}
function addRelatedCandidate(candidateMap, fileRecord, reason, score) {
  if (!fileRecord || !fileRecord.indexed) {
    return;
  }
  const existing = candidateMap.get(fileRecord.path) ?? {
    path: fileRecord.path,
    file_id: fileRecord.file_id,
    file_class: fileRecord.file_class,
    preview: fileRecord.preview || "",
    score: 0,
    reasons: /* @__PURE__ */ new Set()
  };
  existing.score += score;
  existing.reasons.add(reason);
  if (!hasText(existing.preview) && hasText(fileRecord.preview)) {
    existing.preview = fileRecord.preview;
  }
  candidateMap.set(fileRecord.path, existing);
}
function resolveReferencedPath(state, reference, basenameMap) {
  const normalizedReference = normalizeRepoPath(reference);
  const exactMatch = state.filesByPath.get(normalizedReference) ?? state.filesByPath.get(path9.posix.normalize(normalizedReference));
  if (exactMatch?.indexed) {
    return exactMatch;
  }
  const basename = path9.posix.basename(normalizedReference);
  const basenameMatches = basenameMap.get(basename) ?? [];
  if (basenameMatches.length === 1) {
    return basenameMatches[0];
  }
  return null;
}
function collectRelatedFiles(state, query, topChunks, topFiles) {
  const topFilePaths = new Set(topFiles.map((file) => file.path));
  const relatedCandidates = /* @__PURE__ */ new Map();
  const indexedFileRecords = state.fileRecords.filter((fileRecord) => fileRecord.indexed);
  const basenameMap = /* @__PURE__ */ new Map();
  const stemMap = /* @__PURE__ */ new Map();
  const directoryMap = /* @__PURE__ */ new Map();
  const salientIdentifiers = /* @__PURE__ */ new Set();
  const salientTitleTokens = /* @__PURE__ */ new Set();
  for (const fileRecord of indexedFileRecords) {
    const basename = path9.posix.basename(fileRecord.path);
    const stem = pathStem(fileRecord.path);
    const directory = path9.posix.dirname(fileRecord.path);
    if (!basenameMap.has(basename)) {
      basenameMap.set(basename, []);
    }
    basenameMap.get(basename).push(fileRecord);
    if (!stemMap.has(stem)) {
      stemMap.set(stem, []);
    }
    stemMap.get(stem).push(fileRecord);
    if (!directoryMap.has(directory)) {
      directoryMap.set(directory, []);
    }
    directoryMap.get(directory).push(fileRecord);
  }
  for (const records of basenameMap.values()) {
    records.sort((left, right) => left.path.localeCompare(right.path));
  }
  for (const records of stemMap.values()) {
    records.sort((left, right) => left.path.localeCompare(right.path));
  }
  for (const records of directoryMap.values()) {
    records.sort((left, right) => left.path.localeCompare(right.path));
  }
  const referenceText = [
    ...topChunks.slice(0, 8).map((chunk) => [chunk.title, chunk.preview, chunk.text].filter(hasText).join("\n")),
    ...topFiles.slice(0, 4).map((file) => [file.preview].filter(hasText).join("\n"))
  ].join("\n");
  const referencedPaths = extractReferencedPaths(
    referenceText,
    new Set(indexedFileRecords.map((fileRecord) => path9.posix.basename(fileRecord.path)))
  );
  for (const referencePath of referencedPaths) {
    const resolvedFile = resolveReferencedPath(state, referencePath, basenameMap);
    if (resolvedFile && !topFilePaths.has(resolvedFile.path)) {
      addRelatedCandidate(relatedCandidates, resolvedFile, `referenced path: ${referencePath}`, 100);
    }
  }
  for (const topFile of topFiles) {
    const topFileRecord = state.filesById.get(topFile.file_id);
    if (!topFileRecord) {
      continue;
    }
    const topStem = pathStem(topFileRecord.path);
    const pairedFiles = stemMap.get(topStem) ?? [];
    const topIsTestLike = isTestLikePath(topFileRecord.path, topFileRecord.file_class);
    for (const candidateFile of pairedFiles) {
      if (candidateFile.path === topFileRecord.path || topFilePaths.has(candidateFile.path)) {
        continue;
      }
      const candidateIsTestLike = isTestLikePath(candidateFile.path, candidateFile.file_class);
      if (candidateIsTestLike === topIsTestLike) {
        continue;
      }
      addRelatedCandidate(
        relatedCandidates,
        candidateFile,
        topIsTestLike ? "paired source file" : "paired test file",
        80
      );
    }
    const topDirectory = path9.posix.dirname(topFileRecord.path);
    const siblingFiles = directoryMap.get(topDirectory) ?? [];
    const topStemTokens = new Set(pathStemTokens(topFileRecord.path));
    for (const candidateFile of siblingFiles) {
      if (candidateFile.path === topFileRecord.path || topFilePaths.has(candidateFile.path)) {
        continue;
      }
      const candidateStemTokens = pathStemTokens(candidateFile.path);
      if (candidateStemTokens.some((token) => topStemTokens.has(token))) {
        addRelatedCandidate(relatedCandidates, candidateFile, "same directory sibling", 40);
      }
    }
    for (const identifier of topFileRecord.top_identifiers ?? []) {
      salientIdentifiers.add(String(identifier.identifier ?? "").toLowerCase());
    }
    for (const sectionTitle of topFileRecord.section_titles ?? []) {
      for (const token of titleTokens(sectionTitle)) {
        salientTitleTokens.add(token);
      }
    }
  }
  for (const topChunk of topChunks) {
    for (const identifier of topChunk.top_identifiers ?? []) {
      salientIdentifiers.add(String(identifier.identifier ?? "").toLowerCase());
    }
    for (const token of titleTokens(topChunk.title || "")) {
      salientTitleTokens.add(token);
    }
  }
  for (const fileRecord of indexedFileRecords) {
    if (topFilePaths.has(fileRecord.path)) {
      continue;
    }
    const candidateIdentifiers = new Set(
      (fileRecord.top_identifiers ?? []).map((identifier) => String(identifier.identifier ?? "").toLowerCase()).filter((identifier) => identifier.length > 0)
    );
    const sharedIdentifier = [...candidateIdentifiers].find((identifier) => salientIdentifiers.has(identifier));
    if (sharedIdentifier) {
      addRelatedCandidate(relatedCandidates, fileRecord, `shared identifier: ${sharedIdentifier}`, 25);
      continue;
    }
    const candidateTitleTokens = /* @__PURE__ */ new Set();
    for (const title of fileRecord.section_titles ?? []) {
      for (const token of titleTokens(title)) {
        candidateTitleTokens.add(token);
      }
    }
    const sharedTitleToken = [...candidateTitleTokens].find((token) => salientTitleTokens.has(token));
    if (sharedTitleToken) {
      addRelatedCandidate(relatedCandidates, fileRecord, `shared title: ${sharedTitleToken}`, 15);
    }
  }
  return [...relatedCandidates.values()].map((candidate) => ({
    path: candidate.path,
    reason: [...candidate.reasons].sort().join("; "),
    score: candidate.score,
    preview: candidate.preview,
    file_class: candidate.file_class
  })).sort((left, right) => right.score - left.score || left.path.localeCompare(right.path)).slice(0, 6);
}
function normalizeQuery(query) {
  const normalizedQueryText = normalizeWhitespace(String(query ?? ""));
  const queryTerms = [...new Set(tokenizeText(normalizedQueryText))];
  return {
    original: String(query ?? ""),
    normalized_text: normalizedQueryText,
    terms: queryTerms
  };
}
function scoreChunkForQuery({ chunkRecord, fileRecord, query, postingsByTerm }) {
  let score = 0;
  const reasons = [];
  const matchedTerms = [];
  const chunkTextLower = chunkRecord.text.toLowerCase();
  const chunkTitleLower = (chunkRecord.title || "").toLowerCase();
  const filePathLower = (fileRecord.path || "").toLowerCase();
  for (const term of query.terms) {
    const postingEntries = postingsByTerm.get(term) ?? [];
    const matchingPosting = postingEntries.find((entry) => entry.chunk_id === chunkRecord.chunk_id);
    if (!matchingPosting) {
      continue;
    }
    matchedTerms.push(term);
    score += 3;
    score += Math.min(6, Math.log2(matchingPosting.tf + 1) * 2);
  }
  if (matchedTerms.length > 0) {
    reasons.push(`matched ${matchedTerms.length} query term(s)`);
  }
  if (query.normalized_text && chunkTextLower.includes(query.normalized_text.toLowerCase())) {
    score += 10;
    reasons.push("exact phrase match");
  }
  if (hasText(chunkRecord.title) && query.terms.some((term) => chunkTitleLower.includes(term))) {
    score += 6;
    reasons.push("title/section match");
  }
  if (query.terms.some((term) => filePathLower.includes(term))) {
    score += 5;
    reasons.push("path match");
  }
  if (matchedTerms.length === query.terms.length && query.terms.length > 1) {
    score += 8;
    reasons.push("all query terms present");
  }
  const identifierStrings = (chunkRecord.top_identifiers ?? []).map((item) => (item.identifier || "").toLowerCase());
  if (query.terms.some((term) => identifierStrings.includes(term))) {
    score += 4;
    reasons.push("identifier match");
  }
  if (fileRecord.file_class === "test" && query.terms.some((term) => /test|spec/.test(term))) {
    score += 2;
    reasons.push("test-class boost");
  }
  if (fileRecord.file_class === "doc" && query.terms.some((term) => /room|encounter|guide|manual|docs?|lore|campaign/.test(term))) {
    score += 2;
    reasons.push("doc-class boost");
  }
  if (fileRecord.file_class === "config" && query.terms.some((term) => /config|setting|env|yaml|json/.test(term))) {
    score += 2;
    reasons.push("config-class boost");
  }
  const density = matchedTerms.length / Math.max(1, chunkRecord.line_count);
  score += density * 10;
  return {
    chunk_id: chunkRecord.chunk_id,
    file_id: fileRecord.file_id,
    path: fileRecord.path,
    title: chunkRecord.title,
    kind: chunkRecord.kind,
    start_line: chunkRecord.start_line,
    end_line: chunkRecord.end_line,
    preview: chunkRecord.preview,
    text: chunkRecord.text,
    matched_terms: matchedTerms,
    score,
    reasons: [...new Set(reasons)]
  };
}
async function loadRelevantPostings(queryTerms, projectRoot) {
  const paths = getPaths(projectRoot);
  const bucketsNeeded = [...new Set(queryTerms.map((t) => {
    const first = t[0] ?? "";
    if (/[a-z]/.test(first)) {
      return first;
    }
    if (/[0-9]/.test(first)) {
      return "num";
    }
    return "other";
  }))];
  const postings = /* @__PURE__ */ new Map();
  for (const bucket of bucketsNeeded) {
    const bucketPath = path9.join(paths.POSTINGS_DIR, `${bucket}.json`);
    try {
      const bucketData = await readJson(bucketPath);
      for (const [term, postingEntries] of Object.entries(bucketData)) {
        postings.set(term, postingEntries);
      }
    } catch {
    }
  }
  return postings;
}
async function runQuery(queryText, projectRoot) {
  const state = await loadCoreState(projectRoot);
  const query = normalizeQuery(queryText);
  if (query.terms.length === 0) {
    return {
      state,
      query,
      topChunks: [],
      topFiles: [],
      relatedFiles: []
    };
  }
  const postings = await loadRelevantPostings(query.terms, projectRoot);
  const postingsByTerm = /* @__PURE__ */ new Map();
  const candidateChunkIds = /* @__PURE__ */ new Set();
  for (const term of query.terms) {
    const postingEntries = postings.get(term) ?? [];
    postingsByTerm.set(term, postingEntries);
    for (const entry of postingEntries) {
      candidateChunkIds.add(entry.chunk_id);
    }
  }
  const chunkScores = [];
  for (const chunkId of candidateChunkIds) {
    const chunkRecord = state.chunksById.get(chunkId);
    if (!chunkRecord) {
      continue;
    }
    const fileRecord = state.filesById.get(chunkRecord.file_id);
    if (!fileRecord || !fileRecord.indexed) {
      continue;
    }
    const scoredChunk = scoreChunkForQuery({ chunkRecord, fileRecord, query, postingsByTerm });
    if (scoredChunk.score > 0) {
      chunkScores.push(scoredChunk);
    }
  }
  chunkScores.sort((left, right) => right.score - left.score || left.path.localeCompare(right.path) || left.start_line - right.start_line);
  const fileScoresMap = /* @__PURE__ */ new Map();
  for (const chunkScore of chunkScores) {
    const existing = fileScoresMap.get(chunkScore.file_id) ?? {
      file_id: chunkScore.file_id,
      path: chunkScore.path,
      score: 0,
      reasons: /* @__PURE__ */ new Set(),
      best_chunks: []
    };
    existing.best_chunks.push(chunkScore);
    existing.best_chunks.sort((left, right) => right.score - left.score);
    existing.best_chunks = existing.best_chunks.slice(0, 3);
    existing.score = existing.best_chunks.reduce((sum, item) => sum + item.score, 0);
    for (const reason of chunkScore.reasons) {
      existing.reasons.add(reason);
    }
    fileScoresMap.set(chunkScore.file_id, existing);
  }
  const topFiles = [...fileScoresMap.values()].map((fileScore) => {
    const fileRecord = state.filesById.get(fileScore.file_id);
    return {
      file_id: fileScore.file_id,
      path: fileScore.path,
      file_class: fileRecord?.file_class ?? "unknown",
      chunk_count: fileRecord?.chunk_count ?? 0,
      preview: fileRecord?.preview ?? "",
      score: fileScore.score,
      reasons: [...fileScore.reasons],
      best_chunks: fileScore.best_chunks.map((chunk) => ({
        chunk_id: chunk.chunk_id,
        start_line: chunk.start_line,
        end_line: chunk.end_line,
        title: chunk.title,
        score: chunk.score
      }))
    };
  }).sort((left, right) => right.score - left.score || left.path.localeCompare(right.path));
  const relatedFiles = collectRelatedFiles(state, query, chunkScores.slice(0, 12), topFiles.slice(0, 8));
  return {
    state,
    query,
    topChunks: chunkScores.slice(0, 12),
    topFiles: topFiles.slice(0, 8),
    relatedFiles
  };
}
async function persistQueryArtifact(kind, queryText, payload, projectRoot) {
  const paths = getPaths(projectRoot);
  const timestamp = (/* @__PURE__ */ new Date()).toISOString().replace(/[:.]/g, "-");
  const slug = safeSlug(queryText, "query").slice(0, QUERY_ARTIFACT_SLUG_MAX_LENGTH).replace(/[-._]+$/g, "") || "query";
  const fileName = `${timestamp}_${safeSlug(kind, "query")}_${slug}.json`;
  await writeJson(path9.join(paths.QUERIES_DIR, fileName), payload);
}
function makePersistableQueryResult(result) {
  const payload = {
    query: result.query,
    topFiles: result.topFiles,
    topChunks: result.topChunks,
    relatedFiles: result.relatedFiles
  };
  if (result.command) {
    payload.command = result.command;
  }
  if (result.suggestedNextCommands) {
    payload.suggestedNextCommands = result.suggestedNextCommands;
  }
  return payload;
}

// src/prettyJson.ts
function prettyJson(value) {
  return JSON.stringify(value, null, 2);
}
var prettyJson_default = prettyJson;

// src/projectMap/commands.ts
var QC = core_exports;
async function persistQueryArtifactBestEffort(kind, queryText, payload, projectRoot) {
  try {
    await QC.persistQueryArtifact(kind, queryText, payload, projectRoot);
  } catch (err) {
    const message = err?.message || String(err);
    console.error(`WARN: could not persist ${kind} artifact: ${message}`);
  }
}
function buildQueryArtifactPayload(command, result) {
  const payload = QC.makePersistableQueryResult({
    ...result,
    command
  });
  return payload;
}
function buildSuggestedNextCommands(result) {
  const suggestions = [];
  if (result.topFiles.length === 0 && result.topChunks.length === 0) {
    suggestions.push("node .ai/scale/project-map.mjs stats");
    suggestions.push(`node .ai/scale/project-map.mjs find ${JSON.stringify(result.query.normalized_text || result.query.original)}`);
    return suggestions;
  }
  const suggestedPaths = /* @__PURE__ */ new Set();
  for (const chunk of result.topChunks.slice(0, 4)) {
    suggestions.push(`node .ai/scale/project-map.mjs inspect ${JSON.stringify(chunk.chunk_id)}`);
    suggestedPaths.add(chunk.path);
  }
  for (const file of result.topFiles.slice(0, 3)) {
    if (!suggestedPaths.has(file.path)) {
      suggestions.push(`node .ai/scale/project-map.mjs inspect ${JSON.stringify(file.path)}`);
    }
  }
  return suggestions;
}
function buildInspectArtifactPayload(target, byChunkId, fileRecord, relatedChunks, owningFile, resolvedBy) {
  if (byChunkId) {
    return {
      command: "inspect",
      target,
      type: "chunk",
      resolvedBy,
      chunk: byChunkId,
      owningFile: owningFile ?? null
    };
  }
  return {
    command: "inspect",
    target,
    type: "file",
    resolvedBy,
    file: fileRecord,
    chunks: relatedChunks
  };
}
async function runStats(projectRoot) {
  const { buildInfo, repoInfo } = await loadCoreState(projectRoot);
  console.log("PROJECT MAP STATS");
  console.log(`version: ${buildInfo.version}`);
  console.log(`project_root: ${repoInfo.project_root}`);
  console.log(`built_at: ${repoInfo.built_at}`);
  console.log(`total_files_seen: ${repoInfo.total_files_seen}`);
  console.log(`indexed_text_files: ${repoInfo.indexed_text_files}`);
  console.log(`skipped_files: ${repoInfo.skipped_files}`);
  console.log(`binary_files: ${repoInfo.binary_files}`);
  console.log(`generated_files_skipped: ${repoInfo.generated_files_skipped}`);
  console.log(`total_chunks: ${repoInfo.total_chunks}`);
  console.log("");
  console.log("MAJOR EXTENSIONS");
  for (const [extension, count] of Object.entries(repoInfo.major_extensions ?? {}).slice(0, 15)) {
    console.log(`- ${extension}: ${count}`);
  }
  console.log("");
  console.log("MAJOR FILE CLASSES");
  for (const [fileClass, count] of Object.entries(repoInfo.major_file_classes ?? {}).slice(0, 15)) {
    console.log(`- ${fileClass}: ${count}`);
  }
  console.log("");
  console.log("MAJOR DIRECTORIES");
  for (const directory of repoInfo.major_directories ?? []) {
    console.log(`- ${directory.path}: files=${directory.recursive_file_count}, indexed=${directory.indexed_file_count}`);
  }
}
async function runFind(queryText, projectRoot, outputMode = "text") {
  const result = await QC.runQuery(queryText, projectRoot);
  const payload = buildQueryArtifactPayload("find", result);
  if (outputMode === "json") {
    console.log(prettyJson_default(payload));
    await persistQueryArtifactBestEffort("find", queryText, payload, projectRoot);
    return;
  }
  console.log(`QUERY: ${result.query.normalized_text || result.query.original}`);
  console.log("");
  console.log("TOP FILES");
  if (result.topFiles.length === 0) {
    console.log("- No matching files found.");
  } else {
    result.topFiles.forEach((file, index) => {
      console.log(`${index + 1}. ${file.path}`);
      console.log(`   score: ${file.score.toFixed(2)}`);
      console.log(`   class: ${file.file_class}`);
      console.log(`   why: ${file.reasons.join(" + ") || "term match"}`);
      if (hasText(file.preview)) {
        console.log(`   preview: ${file.preview}`);
      }
    });
  }
  console.log("");
  console.log("TOP CHUNKS");
  if (result.topChunks.length === 0) {
    console.log("- No matching chunks found.");
  } else {
    result.topChunks.forEach((chunk, index) => {
      console.log(`${index + 1}. [${chunk.chunk_id}] ${chunk.path} lines ${chunk.start_line}-${chunk.end_line}`);
      if (hasText(chunk.title)) {
        console.log(`   title: ${chunk.title}`);
      }
      console.log(`   score: ${chunk.score.toFixed(2)}`);
      console.log(`   why: ${chunk.reasons.join(" + ") || "term match"}`);
      if (hasText(chunk.preview)) {
        console.log(`   preview: ${chunk.preview}`);
      }
    });
  }
  console.log("");
  console.log("RELATED FILES");
  if (result.relatedFiles.length === 0) {
    console.log("- None.");
  } else {
    for (const relatedFile of result.relatedFiles) {
      console.log(`- ${relatedFile.path} (${relatedFile.reason})`);
    }
  }
  await persistQueryArtifactBestEffort("find", queryText, payload, projectRoot);
}
async function runInspect(target, projectRoot, outputMode = "text") {
  const state = await loadCoreState(projectRoot);
  const byFileId = state.filesById.get(target);
  const byFilePath = state.filesByPath.get(target);
  const byChunkId = state.chunksById.get(target);
  if (byChunkId) {
    const owningFile = state.filesById.get(byChunkId.file_id);
    const payload2 = buildInspectArtifactPayload(target, byChunkId, null, [], owningFile, "chunk_id");
    if (outputMode === "json") {
      console.log(prettyJson_default(payload2));
      await persistQueryArtifactBestEffort("inspect", target, payload2, projectRoot);
      return;
    }
    console.log(`INSPECT: ${target}`);
    console.log(`type: chunk`);
    console.log(`path: ${byChunkId.path}`);
    console.log(`file_id: ${byChunkId.file_id}`);
    console.log(`chunk_id: ${byChunkId.chunk_id}`);
    console.log(`lines: ${byChunkId.start_line}-${byChunkId.end_line}`);
    console.log(`kind: ${byChunkId.kind}`);
    console.log(`title: ${byChunkId.title || "(none)"}`);
    console.log(`file_class: ${owningFile?.file_class ?? "unknown"}`);
    console.log(`preview: ${byChunkId.preview || "(none)"}`);
    console.log("");
    console.log("TOP TERMS");
    for (const item of byChunkId.top_terms ?? []) {
      console.log(`- ${item.term}: ${item.count}`);
    }
    console.log("");
    console.log("TOP IDENTIFIERS");
    for (const item of byChunkId.top_identifiers ?? []) {
      console.log(`- ${item.identifier}: ${item.count}`);
    }
    console.log("");
    console.log("TEXT");
    console.log(byChunkId.text);
    await persistQueryArtifactBestEffort("inspect", target, payload2, projectRoot);
    return;
  }
  const fileRecord = byFileId ?? byFilePath;
  const resolvedBy = byFileId ? "file_id" : "file_path";
  if (!fileRecord) {
    throw new Error(`No file or chunk found for inspect target: ${target}`);
  }
  const fileChunks = state.chunksByFileId.get(fileRecord.file_id) ?? [];
  const payload = buildInspectArtifactPayload(target, null, fileRecord, fileChunks, null, resolvedBy);
  if (outputMode === "json") {
    console.log(prettyJson_default(payload));
    await persistQueryArtifactBestEffort("inspect", target, payload, projectRoot);
    return;
  }
  console.log(`INSPECT: ${target}`);
  console.log(`type: file`);
  console.log(`path: ${fileRecord.path}`);
  console.log(`file_id: ${fileRecord.file_id}`);
  console.log(`class: ${fileRecord.file_class}`);
  console.log(`indexed: ${fileRecord.indexed}`);
  console.log(`extension: ${fileRecord.extension || "(none)"}`);
  console.log(`size_bytes: ${fileRecord.size_bytes}`);
  console.log(`line_count: ${fileRecord.line_count}`);
  console.log(`chunk_count: ${fileRecord.chunk_count}`);
  if (!fileRecord.indexed && fileRecord.skip_reason) {
    console.log(`skip_reason: ${fileRecord.skip_reason}`);
  }
  if (hasText(fileRecord.preview)) {
    console.log(`preview: ${fileRecord.preview}`);
  }
  console.log("");
  console.log("SECTION TITLES");
  if ((fileRecord.section_titles ?? []).length === 0) {
    console.log("- None.");
  } else {
    for (const title of fileRecord.section_titles) {
      console.log(`- ${title}`);
    }
  }
  console.log("");
  console.log("TOP TERMS");
  for (const item of fileRecord.top_terms ?? []) {
    console.log(`- ${item.term}: ${item.count}`);
  }
  console.log("");
  console.log("TOP IDENTIFIERS");
  for (const item of fileRecord.top_identifiers ?? []) {
    console.log(`- ${item.identifier}: ${item.count}`);
  }
  console.log("");
  console.log("CHUNKS");
  if (fileChunks.length === 0) {
    console.log("- None.");
  } else {
    for (const chunk of fileChunks) {
      console.log(`- [${chunk.chunk_id}] lines ${chunk.start_line}-${chunk.end_line} | kind=${chunk.kind}${hasText(chunk.title) ? ` | title=${chunk.title}` : ""}`);
      if (hasText(chunk.preview)) {
        console.log(`  preview: ${chunk.preview}`);
      }
    }
  }
  await persistQueryArtifactBestEffort("inspect", target, payload, projectRoot);
}
async function runPack(queryText, projectRoot, outputMode = "text") {
  const result = await QC.runQuery(queryText, projectRoot);
  const payload = {
    ...buildQueryArtifactPayload("pack", result),
    suggestedNextCommands: buildSuggestedNextCommands(result)
  };
  if (outputMode === "json") {
    console.log(prettyJson_default(payload));
    await persistQueryArtifactBestEffort("pack", queryText, payload, projectRoot);
    return;
  }
  console.log(`TASK: ${result.query.normalized_text || result.query.original}`);
  console.log("");
  console.log("LIKELY TARGET FILES");
  if (result.topFiles.length === 0) {
    console.log("- No likely target files found.");
  } else {
    result.topFiles.forEach((file, index) => {
      console.log(`${index + 1}. ${file.path}`);
      console.log(`   score: ${file.score.toFixed(2)}`);
      console.log(`   class: ${file.file_class}`);
      console.log(`   why: ${file.reasons.join(" + ") || "term match"}`);
      if (file.best_chunks.length > 0) {
        const strongestChunk = file.best_chunks[0];
        console.log(`   best_section: ${strongestChunk.start_line}-${strongestChunk.end_line}${hasText(strongestChunk.title) ? ` | ${strongestChunk.title}` : ""}`);
      }
    });
  }
  console.log("");
  console.log("LIKELY SECTIONS");
  if (result.topChunks.length === 0) {
    console.log("- No likely sections found.");
  } else {
    result.topChunks.forEach((chunk, index) => {
      console.log(`${index + 1}. [${chunk.chunk_id}] ${chunk.path} lines ${chunk.start_line}-${chunk.end_line}`);
      if (hasText(chunk.title)) {
        console.log(`   title: ${chunk.title}`);
      }
      console.log(`   why: ${chunk.reasons.join(" + ") || "term match"}`);
      if (hasText(chunk.preview)) {
        console.log(`   preview: ${chunk.preview}`);
      }
    });
  }
  console.log("");
  console.log("RELATED FILES");
  if (result.relatedFiles.length === 0) {
    console.log("- None.");
  } else {
    for (const relatedFile of result.relatedFiles) {
      console.log(`- ${relatedFile.path} (${relatedFile.reason})`);
    }
  }
  console.log("");
  console.log("SUGGESTED NEXT COMMANDS");
  for (const suggestion of payload.suggestedNextCommands) {
    console.log(`- ${suggestion}`);
  }
  await persistQueryArtifactBestEffort("pack", queryText, payload, projectRoot);
}

// src/projectMap/cli.ts
function parseOutputMode(args) {
  const filtered = [];
  let outputMode = "text";
  for (const arg of args) {
    if (arg === "--json") {
      outputMode = "json";
      continue;
    }
    filtered.push(arg);
  }
  return { args: filtered, outputMode };
}
function printHelp(projectRoot) {
  const paths = getPaths(projectRoot);
  const scriptPath = path10.relative(paths.PROJECT_ROOT, fileURLToPath(import.meta.url)) || ".ai/scale/project-map.mjs";
  console.log("ProjectMap v1");
  console.log("");
  console.log("Usage:");
  console.log(`  node ${scriptPath} <command> [args]`);
  console.log("");
  console.log("Commands:");
  console.log("  build");
  console.log("    Rebuilds .ai/scale/state from scratch.");
  console.log("  stats");
  console.log("    Prints a compact high-level project summary.");
  console.log('  find "<query>"');
  console.log("    Prints ranked candidate files and chunks for a query.");
  console.log("    Add --json to return structured output.");
  console.log('  inspect "<path-or-id>"');
  console.log("    Prints structured details for one file or chunk.");
  console.log("    Add --json to return structured output.");
  console.log('  pack "<task-or-question>"');
  console.log("    Prints a compact investigation packet optimized for browser-based work.");
  console.log("    Add --json to return structured output.");
  console.log("  help");
  console.log("    Prints this help text.");
  console.log("");
  console.log("Examples:");
  console.log(`  node ${scriptPath} build`);
  console.log(`  node ${scriptPath} stats`);
  console.log(`  node ${scriptPath} find "sales order rate retrieval"`);
  console.log(`  node ${scriptPath} inspect "application/controllers/QbeSalesOrderViewController.php"`);
  console.log(`  node ${scriptPath} pack "Where does sales order rate retrieval happen?"`);
}
async function main(argv, projectRoot) {
  const args = argv ?? process.argv.slice(2);
  const { args: filteredArgs, outputMode } = parseOutputMode(args);
  const [command, ...rest] = filteredArgs;
  switch (command) {
    case "build":
      if (rest.length > 0) {
        throw new Error("The build command does not accept additional arguments.");
      }
      await runBuild(projectRoot);
      break;
    case "stats":
      if (rest.length > 0) {
        throw new Error("The stats command does not accept additional arguments.");
      }
      await runStats(projectRoot);
      break;
    case "find": {
      const queryText = rest.join(" ").trim();
      if (!queryText) {
        throw new Error("The find command requires a query string.");
      }
      await runFind(queryText, projectRoot, outputMode);
      break;
    }
    case "inspect": {
      const target = rest.join(" ").trim();
      if (!target) {
        throw new Error("The inspect command requires a path or id.");
      }
      await runInspect(target, projectRoot, outputMode);
      break;
    }
    case "pack": {
      const queryText = rest.join(" ").trim();
      if (!queryText) {
        throw new Error("The pack command requires a task or question.");
      }
      await runPack(queryText, projectRoot, outputMode);
      break;
    }
    case "help":
    case "--help":
    case "-h":
    case void 0:
      printHelp(projectRoot);
      break;
    default:
      throw new Error(`Unknown command: ${command}`);
  }
}
main().catch((error) => {
  console.error(`ERROR: ${error.message}`);
  process.exit(1);
});
export {
  main,
  printHelp
};
//# sourceMappingURL=project-map.mjs.map
