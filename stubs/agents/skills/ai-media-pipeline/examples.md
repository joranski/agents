# AI Media Pipeline — Bibles & FFmpeg Automation

## 1. Character & Style Bible (`docs/media/style-bible.yaml`)

```yaml
style_bible:
  version: "1.0"
  palette:
    primary: "#1e293b" # Slate 800
    accent: "#f59e0b"  # Amber 500
    lighting: "Warm directional key light, soft 35mm cinematic bloom"
  aspect_ratio: "16:9"
  resolution: "1920x1080"
  framerate: 24

character_anchor:
  name: "Dr. Evelyn Vance"
  description: "Late 30s bio-computational researcher, short dark hair, rectangular horn-rim glasses, navy lab coat over charcoal turtleneck"
  negative_prompt: "cartoon, 3d render, CGI plastic skin, extra limbs, distorted hands, blurry eyes"
```

---

## 2. Static Keyframe Pre-Validation Checklist

Before dispatching video rendering (Runway, Luma, Kling, Sora):
- [ ] Static image generated with exact seed and prompt.
- [ ] Character consistency verified against Style Bible.
- [ ] No anatomical or spatial distortion.
- [ ] Human partner prompted: *"Keyframe approved for rendering? (Y/N)"*

---

## 3. FFmpeg CLI Automation Recipes

### Normalize Codec and Frame Rate (Pre-Stitch)
```bash
ffmpeg -i raw_clip.mp4 -vf "scale=1920:1080:force_original_aspect_ratio=decrease,pad=1920:1080:(ow-iw)/2:(oh-ih)/2,fps=24" -c:v libx264 -pix_fmt yuv420p normalized_clip.mp4
```

### Concatenate Normalized Clips
```bash
# clips.txt:
# file 'clip1.mp4'
# file 'clip2.mp4'
ffmpeg -f concat -safe 0 -i clips.txt -c copy output_stitched.mp4
```

### Mix Voiceover & Background Music (Duck Audio)
```bash
ffmpeg -i output_stitched.mp4 -i voiceover.wav -i bg_music.mp3 -filter_complex \
"[2:a]volume=0.15[bg]; \
 [1:a][bg]amix=inputs=2:duration=first[audio]" \
-map 0:v -map "[audio]" -c:v copy -c:a aac -b:a 192k final_video.mp4
```
