import json

path = r"C:\Users\prach\.gemini\antigravity\brain\94682b3f-bee1-4a4f-97fd-35dded607fbd\.system_generated\logs\transcript.jsonl"
with open(path, "r", encoding="utf-8") as f:
    for line in f:
        data = json.loads(line)
        if data.get("source") == "USER_EXPLICIT" and "type" in data and data["type"] == "USER_INPUT":
            content = data.get("content", "")
            if "profit" in content.lower() or "report" in content.lower() or "loss" in content.lower():
                print(f"Index: {data.get('step_index')} | Date: {data.get('created_at')}")
                print(content)
                print("-" * 50)
