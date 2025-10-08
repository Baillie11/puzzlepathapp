// JavaScript fix to auto-detect input types from task descriptions
// Add this to the generateAnswerSection function in index.html

function detectInputTypeFromTask(clue, index) {
    const task = (clue.task || '').toLowerCase();
    const clueText = (clue.clue || '').toLowerCase();
    
    // Keywords that indicate text input is needed
    const textKeywords = [
        'type', 'enter', 'write', 'spell', 'word', 'letter', 'number',
        'what is', 'how many', 'count', 'find the word', 'read the sign'
    ];
    
    // Keywords that indicate photo input is needed  
    const photoKeywords = [
        'photo', 'picture', 'selfie', 'take a', 'snap', 'pose', 'image'
    ];
    
    const fullText = task + ' ' + clueText;
    
    // Check for photo requirements first
    if (photoKeywords.some(keyword => fullText.includes(keyword))) {
        return 'photo';
    }
    
    // Check for text input requirements
    if (textKeywords.some(keyword => fullText.includes(keyword))) {
        return 'text';
    }
    
    // Default to none (just completion marking)
    return 'none';
}

// Enhanced generateAnswerSection function
function generateAnswerSection(clue, index) {
    // Debug logging
    console.log('DEBUG - generateAnswerSection called for clue:', clue.title, 'input_type:', clue.input_type);
    
    // Auto-detect input type if missing or 'none' but task suggests otherwise
    let inputType = clue.input_type || 'none';
    
    if (inputType === 'none' || !inputType) {
        const detectedType = detectInputTypeFromTask(clue, index);
        console.log('DEBUG - Auto-detected input type:', detectedType, 'for clue:', clue.title);
        inputType = detectedType;
    }
    
    // Continue with the rest of the original function...
    if (inputType === 'none') {
        return `
            <div class="answer-section">
                <div class="answer-feedback" id="feedback-${index}"></div>
                <button class="submit-answer-btn" onclick="submitAnswer(${index})">
                    ✅ Mark as Complete
                </button>
            </div>
        `;
    }
    
    let inputHTML = '';
    let submitText = '✅ Submit Answer';
    
    switch (inputType) {
        case 'text':
            inputHTML = `
                <label class="answer-label">Your Answer:</label>
                <input type="text" class="answer-input" id="answer-${index}" 
                       placeholder="Type your answer here..." required>
                ${clue.is_case_sensitive ? '<small>⚠️ Answer is case-sensitive</small>' : ''}
            `;
            break;
            
        case 'number':
            const minMax = clue.min_value !== null && clue.max_value !== null 
                ? `min="${clue.min_value}" max="${clue.max_value}"` : '';
            const placeholder = clue.min_value !== null && clue.max_value !== null 
                ? `Enter a number between ${clue.min_value} and ${clue.max_value}` 
                : 'Enter a number';
            inputHTML = `
                <label class="answer-label">Your Answer:</label>
                <input type="number" class="answer-input" id="answer-${index}" 
                       placeholder="${placeholder}" ${minMax} step="any" required>
            `;
            break;
            
        case 'multiple_choice':
            if (clue.answer_options) {
                const options = Object.entries(clue.answer_options)
                    .map(([key, value]) => `
                        <div class="multiple-choice">
                            <label>
                                <input type="radio" name="answer-${index}" value="${key}" required>
                                <strong>${key}:</strong> ${value}
                            </label>
                        </div>
                    `).join('');
                inputHTML = `
                    <label class="answer-label">Choose your answer:</label>
                    ${options}
                `;
            }
            break;
            
        case 'photo':
            submitText = '📸 Upload Photo';
            inputHTML = `
                <label class="answer-label">Upload a photo:</label>
                <div class="photo-upload" id="photo-upload-${index}" 
                     onclick="document.getElementById('photo-${index}').click()"
                     ondrop="handlePhotoDrop(event, ${index})" 
                     ondragover="handlePhotoDragOver(event)"
                     ondragleave="handlePhotoDragLeave(event)">
                    <p>📸 Drag & drop a photo here, or click to select</p>
                    <input type="file" class="photo-input" id="photo-${index}" 
                           accept="image/*" onchange="handlePhotoSelect(${index})" style="display: none;">
                    <img class="photo-preview" id="preview-${index}" alt="Photo preview">
                </div>
            `;
            break;
            
        default:
            console.error('Unknown input_type:', inputType, '(original:', clue.input_type, ') for clue:', clue.title);
            // Default to text input for unknown types
            inputHTML = `
                <label class="answer-label">Your Answer:</label>
                <input type="text" class="answer-input" id="answer-${index}" 
                       placeholder="Type your answer here..." required>
                <small style="color: #666;">Note: Auto-detected as text input</small>
            `;
            break;
    }
    
    return `
        <div class="answer-section" id="answer-section-${index}">
            ${inputHTML}
            <div class="answer-feedback" id="feedback-${index}"></div>
            <button class="submit-answer-btn" id="submit-${index}" onclick="submitAnswer(${index})">
                ${submitText}
            </button>
        </div>
    `;
}