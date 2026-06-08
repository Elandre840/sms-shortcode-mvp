#!/usr/bin/env node
/**
 * Builds demo videos (16:9 + 9:16) with AI voiceover, captions, and royalty-free music.
 * Configure via config.json + scenes.json in the same folder.
 */
import { execFile, spawn } from 'node:child_process';
import { promisify } from 'node:util';
import fs from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import ffmpegPath from '@ffmpeg-installer/ffmpeg';
import { MsEdgeTTS, OUTPUT_FORMAT } from 'msedge-tts';
import sharp from 'sharp';

const execFileAsync = promisify(execFile);
const __dirname = path.dirname(fileURLToPath(import.meta.url));
const FFMPEG = ffmpegPath.path;
const OUT = path.join(__dirname, 'output');
const TMP = path.join(OUT, 'tmp');
const MUSIC_URL = 'https://www.soundhelix.com/examples/mp3/SoundHelix-Song-8.mp3';

const config = JSON.parse(await fs.readFile(path.join(__dirname, 'config.json'), 'utf8'));
const scenes = JSON.parse(await fs.readFile(path.join(__dirname, 'scenes.json'), 'utf8'));
const VOICE = config.voice || 'en-GB-SoniaNeural';
const TARGET_TOTAL = config.targetSeconds || 60;
const theme = config.theme || { background: { r: 10, g: 46, b: 34 }, captionBar: '#0a2e22' };
const endCard = config.endCard;

async function runFfmpeg(args) {
  return new Promise((resolve, reject) => {
    const proc = spawn(FFMPEG, args, { stdio: ['ignore', 'pipe', 'pipe'] });
    let stderr = '';
    proc.stderr.on('data', (d) => { stderr += d; });
    proc.on('close', (code) => {
      if (code === 0) resolve(stderr);
      else reject(new Error(`ffmpeg exit ${code}\n${stderr.slice(-2000)}`));
    });
  });
}

async function probeDuration(file) {
  const ffprobe = FFMPEG.replace(/ffmpeg(\.exe)?$/i, 'ffprobe$1');
  try {
    const { stdout } = await execFileAsync(ffprobe, [
      '-v', 'error', '-show_entries', 'format=duration',
      '-of', 'default=noprint_wrappers=1:nokey=1', file,
    ]);
    return parseFloat(stdout.trim()) || 0;
  } catch {
    const stat = await fs.stat(file);
    return Math.max(3, stat.size / 12000);
  }
}

async function generateVoice(text, outFile) {
  const tts = new MsEdgeTTS();
  await tts.setMetadata(VOICE, OUTPUT_FORMAT.AUDIO_24KHZ_96KBITRATE_MONO_MP3);
  const { audioStream } = tts.toStream(text);
  const chunks = [];
  for await (const chunk of audioStream) chunks.push(chunk);
  await fs.writeFile(outFile, Buffer.concat(chunks));
  return probeDuration(outFile);
}

async function createEndCard(outFile, w, h) {
  const { title, subtitle, link, gradientStart, gradientEnd, accent } = endCard;
  const svg = `<svg width="${w}" height="${h}" xmlns="http://www.w3.org/2000/svg">
    <defs>
      <linearGradient id="bg" x1="0%" y1="0%" x2="100%" y2="100%">
        <stop offset="0%" style="stop-color:${gradientStart}"/>
        <stop offset="100%" style="stop-color:${gradientEnd}"/>
      </linearGradient>
    </defs>
    <rect width="100%" height="100%" fill="url(#bg)"/>
    <text x="50%" y="${h * 0.38}" text-anchor="middle" fill="#ffffff" font-family="Segoe UI, Arial" font-size="${Math.round(w * 0.045)}" font-weight="700">${title}</text>
    <text x="50%" y="${h * 0.48}" text-anchor="middle" fill="#dbeafe" font-family="Segoe UI, Arial" font-size="${Math.round(w * 0.028)}">${subtitle}</text>
    <rect x="${w * 0.15}" y="${h * 0.56}" width="${w * 0.7}" height="${h * 0.12}" rx="16" fill="#ffffff" opacity="0.12"/>
    <text x="50%" y="${h * 0.635}" text-anchor="middle" fill="${accent}" font-family="Consolas, monospace" font-size="${Math.round(w * 0.032)}" font-weight="600">${link}</text>
    <text x="50%" y="${h * 0.78}" text-anchor="middle" fill="#ffffff" font-family="Segoe UI, Arial" font-size="${Math.round(w * 0.024)}" opacity="0.85">⭐ Star on GitHub · Link in description</text>
    <text x="50%" y="${h * 0.86}" text-anchor="middle" fill="#ffffff" font-family="Segoe UI, Arial" font-size="${Math.round(w * 0.022)}" opacity="0.6">Built by Elandre Booth</text>
  </svg>`;
  await sharp(Buffer.from(svg)).png().toFile(outFile);
}

async function prepareFrame(srcImage, caption, outFile, w, h, isVertical) {
  let imgPath = srcImage;
  if (srcImage === '__endcard__') {
    imgPath = path.join(TMP, `endcard_${w}x${h}.png`);
    await createEndCard(imgPath, w, h);
  }

  const meta = await sharp(imgPath).metadata();
  const iw = meta.width || w;
  const ih = meta.height || h;
  const bg = theme.background;
  const barColor = theme.captionBar;

  if (!isVertical) {
    const barH = Math.round(h * 0.14);
    const contentH = h - barH;
    const scale = Math.min(w / iw, contentH / ih) * 0.98;
    const nw = Math.round(iw * scale);
    const nh = Math.round(ih * scale);
    const resized = await sharp(imgPath).resize(nw, nh, { fit: 'inside' }).png().toBuffer();
    const captionSvg = `<svg width="${w}" height="${barH}" xmlns="http://www.w3.org/2000/svg">
      <rect width="100%" height="100%" fill="${barColor}"/>
      <text x="50%" y="55%" text-anchor="middle" dominant-baseline="middle"
        fill="#ffffff" font-family="Segoe UI, Arial" font-size="36" font-weight="600">${caption.replace(/&/g, '&amp;').replace(/</g, '&lt;')}</text>
    </svg>`;
    await sharp({ create: { width: w, height: h, channels: 3, background: bg } })
      .composite([
        { input: resized, top: Math.round((contentH - nh) / 2), left: Math.round((w - nw) / 2) },
        { input: Buffer.from(captionSvg), top: contentH, left: 0 },
      ])
      .jpeg({ quality: 92 })
      .toFile(outFile);
    return;
  }

  const blurred = await sharp(imgPath).resize(w, h, { fit: 'cover' }).blur(18).modulate({ brightness: 0.45 }).toBuffer();
  const contentH = Math.round(h * 0.72);
  const scale = Math.min(w * 0.92 / iw, contentH / ih);
  const nw = Math.round(iw * scale);
  const nh = Math.round(ih * scale);
  const resized = await sharp(imgPath).resize(nw, nh, { fit: 'inside' }).png().toBuffer();
  const barH = Math.round(h * 0.12);
  const captionSvg = `<svg width="${w}" height="${barH}" xmlns="http://www.w3.org/2000/svg">
    <rect width="100%" height="100%" fill="${barColor}" opacity="0.92"/>
    <text x="50%" y="55%" text-anchor="middle" dominant-baseline="middle"
      fill="#ffffff" font-family="Segoe UI, Arial" font-size="30" font-weight="600">${caption.replace(/&/g, '&amp;').replace(/</g, '&lt;')}</text>
  </svg>`;
  await sharp(blurred)
    .composite([
      { input: resized, top: Math.round((contentH - nh) / 2 + h * 0.04), left: Math.round((w - nw) / 2) },
      { input: Buffer.from(captionSvg), top: h - barH, left: 0 },
    ])
    .jpeg({ quality: 92 })
    .toFile(outFile);
}

async function renderScene(scene, index, duration, w, h, isVertical) {
  const frameFile = path.join(TMP, `frame_${index}_${w}.jpg`);
  const audioFile = path.join(TMP, `voice_${index}.mp3`);
  const sceneFile = path.join(TMP, `scene_${index}_${w}.mp4`);
  const srcImage = path.resolve(__dirname, scene.image);
  const audioDur = await probeDuration(audioFile);
  // Never cut voice — scene must run at least through full narration + brief pause
  const sceneDur = Math.max(duration, audioDur + 0.4);

  await prepareFrame(scene.image === '__endcard__' ? '__endcard__' : srcImage, scene.caption, frameFile, w, h, isVertical);
  const fps = 30;
  const frames = Math.max(1, Math.ceil(sceneDur * fps));
  const zoomEnd = 1.06;
  await runFfmpeg([
    '-loop', '1', '-i', frameFile, '-i', audioFile,
    '-vf', `zoompan=z='1+(${zoomEnd}-1)*on/${frames}':x='iw/2-(iw/zoom/2)':y='ih/2-(ih/zoom/2)':d=${frames}:s=${w}x${h}:fps=${fps},format=yuv420p`,
    '-c:v', 'libx264', '-preset', 'fast', '-crf', '22', '-c:a', 'aac', '-b:a', '192k',
    '-t', String(sceneDur), '-y', sceneFile,
  ]);
  return { file: sceneFile, duration: sceneDur };
}

async function downloadMusic(dest, duration) {
  try {
    const res = await fetch(MUSIC_URL);
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    await fs.writeFile(dest, Buffer.from(await res.arrayBuffer()));
  } catch (e) {
    console.warn('Music download failed, generating ambient bed:', e.message);
    await runFfmpeg([
      '-f', 'lavfi', '-i', `sine=frequency=220:duration=${duration + 5}`,
      '-af', `volume=0.06,afade=t=in:st=0:d=2,afade=t=out:st=${duration}:d=4`, '-y', dest,
    ]);
  }
}

async function assembleVideo(sceneFiles, musicFile, outFile, totalDuration) {
  const listFile = path.join(TMP, 'concat.txt');
  await fs.writeFile(listFile, sceneFiles.map((f) => `file '${f.replace(/\\/g, '/')}'`).join('\n'));
  const concatOut = path.join(TMP, 'concat_raw.mp4');
  await runFfmpeg(['-f', 'concat', '-safe', '0', '-i', listFile, '-c', 'copy', '-y', concatOut]);
  await runFfmpeg([
    '-i', concatOut, '-i', musicFile,
    '-filter_complex',
    `[1:a]volume=0.12,afade=t=in:st=0:d=2,afade=t=out:st=${Math.max(0, totalDuration - 4)}:d=4[music];` +
    `[0:a][music]amix=inputs=2:duration=first:dropout_transition=2[aout]`,
    '-map', '0:v', '-map', '[aout]', '-c:v', 'copy', '-c:a', 'aac', '-b:a', '192k',
    '-y', outFile,
  ]);
}

async function buildFormat(w, h, isVertical, label) {
  console.log(`\n=== Building ${label} (${w}x${h}) ===`);
  const durations = await Promise.all(scenes.map((_, i) => probeDuration(path.join(TMP, `voice_${i}.mp3`))));
  const rawTotal = durations.reduce((a, b) => a + b, 0);
  const tailPad = 0.4;
  let scaled = durations.map((d) => d + tailPad);
  let total = scaled.reduce((a, b) => a + b, 0);

  if (total < TARGET_TOTAL - 1) {
    const extra = (TARGET_TOTAL - total) / scenes.length;
    scaled = scaled.map((d) => d + extra);
    total = scaled.reduce((a, b) => a + b, 0);
  }

  console.log(`  Voice raw: ${rawTotal.toFixed(1)}s → video length: ${total.toFixed(1)}s (full narration preserved)`);
  const sceneResults = [];
  for (let i = 0; i < scenes.length; i++) {
    console.log(`  Rendering scene ${i + 1}/${scenes.length} (min ${scaled[i].toFixed(1)}s)`);
    sceneResults.push(await renderScene(scenes[i], i, scaled[i], w, h, isVertical));
  }
  const sceneFiles = sceneResults.map((r) => r.file);
  total = sceneResults.reduce((sum, r) => sum + r.duration, 0);

  const musicFile = path.join(TMP, `music_${label}.mp3`);
  console.log('  Downloading royalty-free background music...');
  await downloadMusic(musicFile, total);

  const prefix = config.outputPrefix || 'demo';
  const outFile = path.join(OUT, `${prefix}_${label}.mp4`);
  await assembleVideo(sceneFiles, musicFile, outFile, total);
  console.log(`  ✓ ${outFile} (${total.toFixed(1)}s)`);
  return outFile;
}

console.log(`${config.projectName} — demo video builder`);
console.log('FFmpeg:', FFMPEG);
await fs.mkdir(TMP, { recursive: true });
await fs.mkdir(OUT, { recursive: true });

for (let i = 0; i < scenes.length; i++) {
  const audioFile = path.join(TMP, `voice_${i}.mp3`);
  if (await fs.stat(audioFile).catch(() => false)) {
    console.log(`TTS ${i + 1}/${scenes.length}: cached — ${scenes[i].caption}`);
  } else {
    console.log(`TTS ${i + 1}/${scenes.length}: ${scenes[i].caption}`);
    await generateVoice(scenes[i].voice, audioFile);
  }
}

const formatArg = process.argv.find((a) => a.startsWith('--format='))?.split('=')[1];
const outputs = [];

if (!formatArg || formatArg === '16x9') {
  outputs.push(await buildFormat(1920, 1080, false, '16x9'));
}
if (!formatArg || formatArg === '9x16') {
  outputs.push(await buildFormat(1080, 1920, true, '9x16'));
}

console.log('\nDone!');
outputs.forEach((f) => console.log(' ', f));
